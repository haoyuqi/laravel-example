<?php

namespace Tests\Feature\Console;

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

    public function test_save_visits_count_persists_data_and_updates_on_rerun(): void
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

        // Update Redis count and rerun: should updateOrCreate rather than insert duplicate
        $this->redisConnection->set($this->pvKey, '50');
        $this->artisan('save:visits-count')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(2, VisitorStatistics::count());
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

    public function test_save_visits_count_succeeds_even_when_soft_deleted_record_exists(): void
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
        $this->assertDatabaseHas('visitor_statistics', [
            'type' => 'uv',
            'date' => $this->dateStr,
            'count' => 1,
            'deleted_at' => null,
        ]);
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
