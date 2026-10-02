<?php

namespace Tests\Feature\Http;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\DB;
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

    public function test_liveness_does_not_probe_database_or_redis(): void
    {
        DB::shouldReceive('select')->never();
        Redis::shouldReceive('ping')->never();
        Redis::shouldReceive('connection')->never();

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
        // A Redis-backed session must not turn a degraded probe into a 500.
        config(['session.driver' => 'redis']);
        Redis::shouldReceive('connection')->never();
        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \RuntimeException('Redis unreachable'));

        $response = $this->get('/health/ready');

        $response->assertStatus(200)
            ->assertHeaderMissing('Set-Cookie')
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
        $this->withoutExceptionHandling();
        config(['session.driver' => 'database']);
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

    public function test_health_routes_do_not_run_session_middleware(): void
    {
        foreach (['/up', '/health/ready'] as $path) {
            $route = app('router')->getRoutes()->match(Request::create($path));

            $this->assertNotContains(StartSession::class,
                app('router')->gatherRouteMiddleware($route));
        }
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
