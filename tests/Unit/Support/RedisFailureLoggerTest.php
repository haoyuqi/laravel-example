<?php

namespace Tests\Unit\Support;

use App\Support\RedisFailureLogger;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RedisFailureLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RedisFailureLogger::reset();
    }

    protected function tearDown(): void
    {
        RedisFailureLogger::reset();
        parent::tearDown();
    }

    public function test_first_call_logs_warning_verbatim_and_returns_true(): void
    {
        $message = 'blacklist cache read failure';
        $context = ['ip' => '127.0.0.1', 'error' => 'Connection refused'];

        Log::shouldReceive('warning')
            ->once()
            ->with($message, $context);

        $result = RedisFailureLogger::report($message, $context);

        $this->assertTrue($result);
    }

    public function test_subsequent_call_within_window_is_suppressed(): void
    {
        $message = 'pv/uv count failure';

        Log::shouldReceive('warning')
            ->once()
            ->with($message, \Mockery::type('array'));

        $first = RedisFailureLogger::report($message, ['error' => 'timeout'], windowSeconds: 10.0);
        $second = RedisFailureLogger::report($message, ['error' => 'timeout'], windowSeconds: 10.0);

        $this->assertTrue($first);
        $this->assertFalse($second);
    }

    public function test_different_messages_are_rate_limited_independently(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('message A', ['a' => 1]);

        Log::shouldReceive('warning')
            ->once()
            ->with('message B', ['b' => 2]);

        $resultA = RedisFailureLogger::report('message A', ['a' => 1]);
        $resultB = RedisFailureLogger::report('message B', ['b' => 2]);

        $this->assertTrue($resultA);
        $this->assertTrue($resultB);
    }

    public function test_reset_clears_throttle_state(): void
    {
        $message = 'test reset message';

        Log::shouldReceive('warning')
            ->twice()
            ->with($message, []);

        $first = RedisFailureLogger::report($message, []);
        $this->assertTrue($first);

        // Suppressed before reset
        $second = RedisFailureLogger::report($message, []);
        $this->assertFalse($second);

        RedisFailureLogger::reset();

        // Allowed after reset
        $third = RedisFailureLogger::report($message, []);
        $this->assertTrue($third);
    }
}
