<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class LivenessReadinessTest extends TestCase
{
    private object $redisManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redisManager = app('redis');
    }

    public function test_liveness_endpoint_returns_200_when_healthy(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_liveness_endpoint_survives_redis_failure_and_logs_warning_via_listener(): void
    {
        Log::shouldReceive('warning')
            ->atLeast()
            ->once()
            ->with('Readiness check: redis degraded', \Mockery::type('array'));

        Redis::shouldReceive('ping')
            ->andThrow(new \RuntimeException('Redis unreachable'));

        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_readiness_endpoint_returns_ready_when_all_healthy(): void
    {
        $response = $this->get('/health/ready');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ready',
                'dependencies' => [
                    'database' => 'healthy',
                    'redis' => 'healthy',
                ],
            ]);
    }

    public function test_readiness_endpoint_reports_degraded_when_redis_fails(): void
    {
        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \RuntimeException('Redis unreachable'));

        $response = $this->get('/health/ready');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'degraded',
                'dependencies' => [
                    'database' => 'healthy',
                    'redis' => 'degraded',
                ],
            ]);
    }

    public function test_readiness_endpoint_reports_503_when_database_fails(): void
    {
        DB::shouldReceive('select')
            ->with('SELECT 1')
            ->once()
            ->andThrow(new \RuntimeException('Database unreachable'));

        $response = $this->get('/health/ready');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'dependencies' => [
                    'database' => 'unhealthy',
                ],
            ]);
    }

    protected function tearDown(): void
    {
        try {
            \Mockery::close();
        } finally {
            Redis::clearResolvedInstance('redis');
            $this->app->instance('redis', $this->redisManager);
            DB::clearResolvedInstances();
            parent::tearDown();
        }
    }
}
