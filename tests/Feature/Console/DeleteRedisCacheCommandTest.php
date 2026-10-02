<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DeleteRedisCacheCommandTest extends TestCase
{
    private object $redisManager;

    private object $redisConnection;

    private string $testKey;

    protected function setUp(): void
    {
        parent::setUp();

        $prefix = 'test_del_cache_'.bin2hex(random_bytes(8)).'_';
        config(['database.redis.options.prefix' => $prefix]);
        $this->redisManager = app('redis');
        $this->redisConnection = $this->redisManager->connection();

        $this->testKey = 'test_delete_key_'.bin2hex(random_bytes(4));
    }

    public function test_delete_redis_cache_deletes_existing_key(): void
    {
        $this->redisConnection->set($this->testKey, 'value');
        $this->assertSame('value', $this->redisConnection->get($this->testKey));

        $this->artisan('delete:redis-cache', ['key' => $this->testKey])
            ->assertExitCode(Command::SUCCESS);

        $this->assertNull($this->redisConnection->get($this->testKey));
    }

    public function test_delete_redis_cache_handles_non_existent_key(): void
    {
        $this->artisan('delete:redis-cache', ['key' => 'non_existent_key'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_delete_redis_cache_gracefully_handles_redis_failure(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('delete redis cache failure', \Mockery::on(fn (array $context): bool => $context['key'] === 'failing_key' && str_contains($context['error'], 'Redis connection failed')
            ));

        Redis::shouldReceive('exists')
            ->once()
            ->andThrow(new \RuntimeException('Redis connection failed'));

        $this->artisan('delete:redis-cache', ['key' => 'failing_key'])
            ->assertExitCode(Command::FAILURE);
    }

    protected function tearDown(): void
    {
        try {
            \Mockery::close();
        } finally {
            Redis::clearResolvedInstance('redis');
            $this->app->instance('redis', $this->redisManager);

            try {
                $this->redisConnection->del($this->testKey);
            } finally {
                parent::tearDown();
            }
        }
    }
}
