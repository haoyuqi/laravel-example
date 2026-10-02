<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RedisFailureLogger
{
    /**
     * Local fallback timestamps if the dedicated file store is unavailable.
     *
     * @var array<string, float>
     */
    protected array $lastReportedAt = [];

    /**
     * Default rate-limit window in seconds.
     */
    protected float $defaultWindowSeconds = 60.0;

    /**
     * Report a Redis failure with cross-request, per-host rate limiting.
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

        try {
            // FileStore::add is atomic across PHP-FPM workers on this host.
            if (! Cache::store('redis_failure_logs')->add($this->key($message), true, max(1, (int) ceil($window)))) {
                return false;
            }
        } catch (\Throwable) {
            // Logging degradation must not introduce another request failure.
            if (isset($this->lastReportedAt[$message]) && ($now - $this->lastReportedAt[$message]) < $window) {
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
        foreach (array_keys($this->lastReportedAt) as $message) {
            try {
                Cache::store('redis_failure_logs')->forget($this->key($message));
            } catch (\Throwable) {
                // The in-memory fallback can still be reset if storage is down.
            }
        }

        $this->lastReportedAt = [];
    }

    private function key(string $message): string
    {
        return 'warning:'.hash('sha256', $message);
    }
}
