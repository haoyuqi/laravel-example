<?php

namespace Tests\Unit\Service;

use App\Models\BlackList;
use App\Service\BlackListService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class BlackListServiceKeyTest extends TestCase
{
    public function test_cache_key_format(): void
    {
        $service = $this->app->make(BlackListService::class);

        $this->assertSame('black_list_'.now()->toDateString(), $service->cacheKey());
    }

    public function test_cache_key_with_custom_date(): void
    {
        $service = $this->app->make(BlackListService::class);

        $this->assertSame(
            'black_list_2026-01-15',
            $service->cacheKey(Carbon::parse('2026-01-15'))
        );
    }

    public function test_forget_ip_calls_hdel(): void
    {
        Redis::shouldReceive('hdel')
            ->once()
            ->with('black_list_'.now()->toDateString(), '1.2.3.4');

        $service = $this->app->make(BlackListService::class);

        $service->forgetIp('1.2.3.4');
    }

    public function test_forget_ip_logs_warning_on_redis_failure(): void
    {
        Redis::shouldReceive('hdel')->andThrow(new Exception('redis down'));
        Log::shouldReceive('warning')->once();

        $service = $this->app->make(BlackListService::class);

        $service->forgetIp('1.2.3.4');
    }

    public function test_touch_ttl_calls_expire_with_48_hours(): void
    {
        $key = 'black_list_'.now()->toDateString();

        Redis::shouldReceive('expire')->once()->with($key, 172800);

        $this->app->make(BlackListService::class)->touchTtl($key);
    }

    public function test_check_ip_single_hget_read_path(): void
    {
        Redis::shouldReceive('hget')
            ->once()
            ->with('black_list_'.now()->toDateString(), '1.2.3.4')
            ->andReturn('1');
        Redis::shouldNotReceive('hexists');

        $result = $this->app->make(BlackListService::class)->checkIp('1.2.3.4', '/');

        $this->assertTrue($result);
    }

    public function test_check_ip_cache_miss_queries_db_and_writes_with_ttl(): void
    {
        BlackList::withoutEvents(fn () => BlackList::create(['ip' => '5.6.7.8']));
        $key = 'black_list_'.now()->toDateString();

        Redis::shouldReceive('hget')->once()->andReturn(null);
        Redis::shouldReceive('hset')->once()->with($key, '5.6.7.8', 1);
        Redis::shouldReceive('expire')->once()->with($key, 172800);

        $result = $this->app->make(BlackListService::class)->checkIp('5.6.7.8', '/');

        $this->assertTrue($result);
    }

    public function test_check_ip_false_hget_representation_is_treated_as_cache_miss(): void
    {
        BlackList::withoutEvents(fn () => BlackList::create(['ip' => '4.3.2.1']));
        $key = 'black_list_'.now()->toDateString();

        Redis::shouldReceive('hget')->once()->andReturn(false);
        Redis::shouldReceive('hset')->once()->with($key, '4.3.2.1', 1);
        Redis::shouldReceive('expire')->once()->with($key, 172800);

        $this->assertTrue($this->app->make(BlackListService::class)->checkIp('4.3.2.1', '/'));
    }

    public function test_check_ip_read_failure_falls_back_to_db(): void
    {
        BlackList::withoutEvents(fn () => BlackList::create(['ip' => '9.9.9.9']));

        Redis::shouldReceive('hget')->once()->andThrow(new Exception('redis down'));
        Redis::shouldReceive('hset')->once();
        Redis::shouldReceive('expire')->once();
        Log::shouldReceive('warning')
            ->once()
            ->with('blacklist cache read failure', \Mockery::any());

        $result = $this->app->make(BlackListService::class)->checkIp('9.9.9.9', '/');

        $this->assertTrue($result);
    }

    public function test_check_ip_write_failure_returns_db_result_and_logs(): void
    {
        BlackList::withoutEvents(fn () => BlackList::create(['ip' => '8.8.4.4']));

        Redis::shouldReceive('hget')->once()->andReturn(null);
        Redis::shouldReceive('hset')->once()->andThrow(new Exception('redis down'));
        Log::shouldReceive('warning')
            ->once()
            ->with('blacklist cache write failure', \Mockery::any());

        $result = $this->app->make(BlackListService::class)->checkIp('8.8.4.4', '/');

        $this->assertTrue($result);
    }
}
