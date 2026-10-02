<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SaveVisitsCountCommand;
use App\Models\VisitorStatistics;
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

    public function test_save_visits_count_appends_snapshots_without_rewriting_history(): void
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

        // Idempotency/deduplication is deliberately deferred to #141.
        $this->redisConnection->set($this->pvKey, '50');
        $this->artisan('save:visits-count')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(4, VisitorStatistics::count());
        $this->assertDatabaseHas('visitor_statistics', [
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

    public function test_save_visits_count_preserves_soft_deleted_records(): void
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
        $this->assertSoftDeleted('visitor_statistics', ['id' => $trashed->id, 'count' => 10]);
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

    public function test_legacy_duplicate_statistics_are_preserved(): void
    {
        foreach ([20, 30] as $count) {
            VisitorStatistics::create(['type' => 'pv', 'date' => $this->dateStr, 'count' => $count]);
        }
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');
        $this->redisConnection->set($this->pvKey, '40');

        $this->artisan('save:visits-count')->assertExitCode(Command::SUCCESS);

        $this->assertSame([20, 30, 40], VisitorStatistics::where('type', 'pv')->orderBy('id')->pluck('count')->map(fn ($count) => (int) $count)->all());
    }

    public function test_uv_and_pv_are_rolled_back_together_if_database_write_fails(): void
    {
        $this->redisConnection->sadd($this->uvKey, '198.51.100.1');
        $this->redisConnection->set($this->pvKey, '42');
        VisitorStatistics::saving(function (VisitorStatistics $statistic): void {
            if ($statistic->type === 'pv') {
                throw new \RuntimeException('Simulated PV write failure');
            }
        });

        try {
            app(SaveVisitsCountCommand::class)->handle();
            $this->fail('Expected the PV write to fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated PV write failure', $e->getMessage());
            $this->assertSame(0, VisitorStatistics::count());
        } finally {
            VisitorStatistics::flushEventListeners();
        }
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
