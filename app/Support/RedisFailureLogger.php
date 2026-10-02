<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class RedisFailureLogger
{
    /**
     * Per-process/container timestamps of the last reported warnings by message key.
     *
     * @var array<string, float>
     */
    protected array $lastReportedAt = [];

    /**
     * Default rate-limit window in seconds.
     */
    protected float $defaultWindowSeconds = 60.0;

    /**
     * Report a Redis failure with per-process rate limiting.
     * Calls Log::warning($message, $context) verbatim.
     *
     * @param  string  $message  The warning message (first argument to Log::warning)
     * @param  array<string, mixed>  $context  The context payload
     * @param  float|null  $windowSeconds  Rate limit window in seconds (null = default)
     * @return bool True if logged, false if rate-limited
     */
    public static function report(string $message, array $context = [], ?float $windowSeconds = null): bool
    {
        return app(self::class)->log($message, $context, $windowSeconds);
    }

    /**
     * Instance implementation of report.
     *
     * @param  array<string, mixed>  $context
     */
    public function log(string $message, array $context = [], ?float $windowSeconds = null): bool
    {
        $window = $windowSeconds ?? $this->defaultWindowSeconds;
        $now = microtime(true);

        if (isset($this->lastReportedAt[$message])) {
            if (($now - $this->lastReportedAt[$message]) < $window) {
                return false;
            }
        }

        $this->lastReportedAt[$message] = $now;

        Log::warning($message, $context);

        return true;
    }

    /**
     * Reset the rate limit timestamps.
     */
    public static function reset(): void
    {
        if (app()->bound(self::class)) {
            app(self::class)->clear();
        }
    }

    /**
     * Clear recorded timestamps on this instance.
     */
    public function clear(): void
    {
        $this->lastReportedAt = [];
    }
}
