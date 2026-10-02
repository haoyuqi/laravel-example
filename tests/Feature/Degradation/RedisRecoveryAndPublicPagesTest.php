<?php

namespace Tests\Feature\Degradation;

use App\Filament\Widgets\StatsOverview;
use App\Http\Middleware\RecordVisitors;
use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;
use Tests\TestCase;

class RedisRecoveryAndPublicPagesTest extends TestCase
{
    private object $redisManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redisManager = app('redis');
    }

    public function test_public_page_returns_200_during_redis_outage(): void
    {
        Redis::shouldReceive('sadd')
            ->andThrow(new \RuntimeException('Redis connection refused'));
        Redis::shouldReceive('incr')
            ->andThrow(new \RuntimeException('Redis connection refused'));
        Redis::shouldReceive('hget')
            ->andThrow(new \RuntimeException('Redis connection refused'));
        Redis::shouldReceive('connection')
            ->andThrow(new \RuntimeException('Redis connection refused'));

        $response = $this->get('/time');

        $response->assertStatus(200);
    }

    public function test_public_page_returns_200_when_redis_is_healthy(): void
    {
        $response = $this->get('/time');

        $response->assertStatus(200);
    }

    public function test_health_endpoint_returns_200_during_redis_outage(): void
    {
        Redis::shouldReceive('ping')
            ->andThrow(new \RuntimeException('Redis connection refused'));

        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_filament_dashboard_widget_renders_during_redis_outage(): void
    {
        $admin = User::factory()->create();

        Redis::shouldReceive('get')
            ->andThrow(new \RuntimeException('Redis connection refused'));
        Redis::shouldReceive('scard')
            ->andThrow(new \RuntimeException('Redis connection refused'));

        Livewire::actingAs($admin)
            ->test(StatsOverview::class)
            ->assertSuccessful()
            ->assertSee('今日 PV')
            ->assertSee('今日 UV');
    }

    public function test_recovery_after_redis_restoration(): void
    {
        // 1. Degraded request: sadd throws, skipping incr
        Redis::shouldReceive('sadd')
            ->once()
            ->andThrow(new \RuntimeException('Redis temporary down'));

        $response1 = $this->withoutMiddleware([RecordVisitors::class])
            ->get('/time');
        $response1->assertStatus(200);

        // 2. Restore Redis instance in container
        Redis::clearResolvedInstance('redis');
        $this->app->instance('redis', $this->redisManager);

        // 3. Healthy request without restart
        $response2 = $this->withoutMiddleware([RecordVisitors::class])
            ->get('/time');
        $response2->assertStatus(200);
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
