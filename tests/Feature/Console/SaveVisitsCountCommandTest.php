<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SaveVisitsCountCommand;
use App\Models\VisitorStatistics;
use App\Support\VisitorStatisticsRetention;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class SaveVisitsCountCommandTest extends TestCase
{
    private object $redisManager;

    private object $redisConnection;

    private string $uvKey;

    private string $pvKey;

    private string $dateStr;

    protected function setUp(): void
    {
        parent::setUp();

        $prefix = 'test_save_visits_'.bin2hex(random_bytes(8)).'_';
        config(['database.redis.options.prefix' => $prefix]);
        $this->redisManager = app('redis');
        $this->redisConnection = $this->redisManager->connection();

        $yesterday = now()->subDay();
        $this->dateStr = $yesterday->toDateString();
        $this->uvKey = 'uv_set_'.$this->dateStr;
        $this->pvKey = 'pv_count_'.$this->dateStr;
    }

    public function test_retries_update_the_same_daily_snapshot(): void
    {
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1', '198.51.100.2');
        $this->redisConnection->set($this->pvKey, '42');

        $this->artisan('save:visits-count')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('visitor_statistics', [
            'type' => 'uv',
            'date' => $this->dateStr,
            'count' => 2,
        ]);
        $this->assertDatabaseHas('visitor_statistics', [
            'type' => 'pv',
            'date' => $this->dateStr,
            'count' => 42,
        ]);

        $this->assertSame(2, VisitorStatistics::count());

        // A scheduler retry refreshes the cumulative snapshot, rather than appending.
        $this->redisConnection->set($this->pvKey, '50');
        $this->artisan('save:visits-count')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(2, VisitorStatistics::count());
        $this->assertDatabaseMissing('visitor_statistics', [
            'type' => 'pv',
            'date' => $this->dateStr,
            'count' => 42,
        ]);
        $this->assertDatabaseHas('visitor_statistics', [
            'type' => 'pv',
            'date' => $this->dateStr,
            'count' => 50,
        ]);
    }

    public function test_save_visits_count_gracefully_handles_redis_failure(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('failed to retrieve visits count from Redis', \Mockery::on(fn (array $context): bool => $context['date'] === $this->dateStr && str_contains($context['error'], 'Redis down')
            ));

        Redis::shouldReceive('scard')
            ->once()
            ->andThrow(new \RuntimeException('Redis down'));

        $this->artisan('save:visits-count')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame(0, VisitorStatistics::count());
    }

    public function test_new_snapshot_restores_the_existing_soft_deleted_row(): void
    {
        $trashed = VisitorStatistics::create([
            'type' => 'uv',
            'date' => $this->dateStr,
            'count' => 10,
        ]);
        $trashed->delete();

        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');
        $this->redisConnection->set($this->pvKey, '20');

        $this->artisan('save:visits-count')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(2, VisitorStatistics::count());
        $this->assertSame(2, VisitorStatistics::withTrashed()->count());
        $this->assertSame($trashed->id, VisitorStatistics::where('type', 'uv')->first()->id);
        $this->assertDatabaseHas('visitor_statistics', [
            'type' => 'uv',
            'date' => $this->dateStr,
            'count' => 1,
            'deleted_at' => null,
        ]);
    }

    public function test_missing_redis_snapshot_does_not_change_existing_statistics(): void
    {
        $original = VisitorStatistics::create(['type' => 'pv', 'date' => $this->dateStr, 'count' => 42]);

        $this->artisan('save:visits-count')->assertExitCode(Command::FAILURE);

        $this->assertSame(1, VisitorStatistics::count());
        $this->assertSame(42, (int) $original->fresh()->count);
    }

    public function test_partial_redis_snapshot_is_not_saved(): void
    {
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');

        $this->artisan('save:visits-count')->assertExitCode(Command::FAILURE);

        $this->assertSame(0, VisitorStatistics::count());
    }

    public function test_expiration_failure_can_be_retried_without_duplicate_rows(): void
    {
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');
        $this->redisConnection->set($this->pvKey, '42');
        Redis::shouldReceive('scard')->once()->andReturn(1);
        Redis::shouldReceive('get')->once()->andReturn('42');
        Redis::shouldReceive('expireat')->once()->andThrow(new \RuntimeException('Redis down'));
        Log::shouldReceive('warning')->once()->with('failed to expire visits count in Redis', \Mockery::type('array'));

        $this->artisan('save:visits-count')->assertExitCode(Command::FAILURE);
        $this->assertSame(2, VisitorStatistics::count());

        Redis::clearResolvedInstance('redis');
        $this->app->instance('redis', $this->redisManager);
        $this->artisan('save:visits-count')->assertExitCode(Command::SUCCESS);
        $this->assertSame(2, VisitorStatistics::count());
        $this->assertGreaterThan(0, $this->redisConnection->ttl($this->pvKey));
    }

    public function test_invalid_redis_count_does_not_replace_a_valid_snapshot(): void
    {
        VisitorStatistics::create(['type' => 'pv', 'date' => $this->dateStr, 'count' => 42]);
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');
        foreach (['-1', 'corrupted'] as $count) {
            $this->redisConnection->set($this->pvKey, $count);
            $this->artisan('save:visits-count')->assertExitCode(Command::FAILURE);
            $this->assertSame(1, VisitorStatistics::count());
            $this->assertDatabaseHas('visitor_statistics', ['type' => 'pv', 'count' => 42]);
        }
    }

    public function test_saved_keys_expire_at_a_fixed_deadline_and_remain_readable_for_retries(): void
    {
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');
        $this->redisConnection->set($this->pvKey, '42');
        $this->artisan('save:visits-count')->assertExitCode(Command::SUCCESS);
        $ttl = $this->redisConnection->ttl($this->pvKey);
        $expected = VisitorStatisticsRetention::expiresAt(now()->subDay()) - time();
        $this->assertEqualsWithDelta($expected, $ttl, 2);
        $this->assertEqualsWithDelta($ttl, $this->redisConnection->ttl($this->uvKey), 2);
        $this->artisan('save:visits-count')->assertExitCode(Command::SUCCESS);
        $this->assertLessThanOrEqual($ttl, $this->redisConnection->ttl($this->pvKey));
        $this->assertSame(2, VisitorStatistics::count());
    }

    public function test_uv_and_pv_updates_roll_back_together_and_keep_redis_snapshot_on_failure(): void
    {
        VisitorStatistics::create(['type' => 'uv', 'date' => $this->dateStr, 'count' => 2]);
        VisitorStatistics::create(['type' => 'pv', 'date' => $this->dateStr, 'count' => 42]);
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');
        $this->redisConnection->set($this->pvKey, '50');
        $command = new class extends SaveVisitsCountCommand
        {
            protected function saveStatistic(string $type, string $date, int $count): void
            {
                parent::saveStatistic($type, $date, $count);
                if ($type === 'pv') {
                    throw new \RuntimeException('Simulated PV write failure');
                }
            }
        };

        try {
            $command->handle();
            $this->fail('Expected the PV write to fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated PV write failure', $e->getMessage());
        }
        $this->assertDatabaseHas('visitor_statistics', ['type' => 'uv', 'count' => 2]);
        $this->assertDatabaseHas('visitor_statistics', ['type' => 'pv', 'count' => 42]);
        $this->assertSame(-1, $this->redisConnection->ttl($this->pvKey));
        $this->artisan('save:visits-count')->assertExitCode(Command::SUCCESS);
        $this->assertSame(2, VisitorStatistics::count());
        $this->assertDatabaseHas('visitor_statistics', ['type' => 'pv', 'count' => 50]);
    }

    protected function tearDown(): void
    {
        try {
            \Mockery::close();
        } finally {
            Redis::clearResolvedInstance('redis');
            $this->app->instance('redis', $this->redisManager);

            try {
                $this->redisConnection->del($this->uvKey, $this->pvKey);
            } finally {
                parent::tearDown();
            }
        }
    }
}
