<?php

namespace Tests\Feature\Http;

use App\Models\BlackList;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthCheckIsolationTest extends TestCase
{
    private object $redisManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redisManager = app('redis');
    }

    public function test_health_check_returns_200_when_redis_is_healthy(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_health_check_returns_200_when_redis_throws_exception(): void
    {
        Redis::shouldReceive('connection')
            ->andThrow(new \RuntimeException('Redis connection refused'));
        Redis::shouldReceive('hget')
            ->andThrow(new \RuntimeException('Redis connection refused'));
        Redis::shouldReceive('sadd')
            ->andThrow(new \RuntimeException('Redis connection refused'));

        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_health_check_is_not_blocked_even_if_requester_ip_is_blacklisted(): void
    {
        $ip = '203.0.113.199';
        BlackList::create(['ip' => $ip]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->get('/up');

        $response->assertStatus(200);
    }

    public function test_health_ready_endpoint_does_not_trigger_pv_uv_counting(): void
    {
        Redis::shouldReceive('sadd')->never();
        Redis::shouldReceive('incr')->never();
        Redis::shouldReceive('ping')->once()->andReturn(true);

        $response = $this->get('/health/ready');

        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        try {
            \Mockery::close();
        } finally {
            Redis::clearResolvedInstance('redis');
            $this->app->instance('redis', $this->redisManager);
            parent::tearDown();
        }
    }
}
