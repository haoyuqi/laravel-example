<?php

namespace App\Service;

use App\Jobs\BlackListLog;
use App\Models\BlackList;
use App\Support\RedisFailureLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Redis;

class BlackListService
{
    private $blackModel;

    public function __construct(BlackList $blackList)
    {
        $this->blackModel = $blackList;
    }

    public function cacheKey(?CarbonInterface $date = null): string
    {
        return 'black_list_'.($date ?? now())->toDateString();
    }

    public function forgetIp(string $ip): void
    {
        try {
            Redis::hdel($this->cacheKey(), $ip);
        } catch (\Throwable $e) {
            RedisFailureLogger::report('blacklist cache invalidation failure', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
    }

    public function touchTtl(string $key): void
    {
        try {
            Redis::expire($key, 172800);
        } catch (\Throwable $e) {
            RedisFailureLogger::report('blacklist cache write failure', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    public function checkIp($ip, $url)
    {
        $cacheKey = $this->cacheKey();

        try {
            $cached = Redis::hget($cacheKey, $ip);
        } catch (\Throwable $e) {
            RedisFailureLogger::report('blacklist cache read failure', ['ip' => $ip, 'error' => $e->getMessage()]);
            $cached = null;
        }

        if ($cached !== null && $cached !== false) {
            $isBlackIp = (bool) $cached;

            if ($isBlackIp) {
                $this->recordBlockedRequest($ip, $url);
            }

            return $isBlackIp;
        }

        $blacklistRecord = $this->blackModel->where('ip', $ip)->first();

        try {
            Redis::hset($cacheKey, $ip, ($blacklistRecord ? 1 : 0));
            $this->touchTtl($cacheKey);
        } catch (\Throwable $e) {
            RedisFailureLogger::report('blacklist cache write failure', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        if ($blacklistRecord) {
            $this->recordBlockedRequest($ip, $url);
        }

        return (bool) $blacklistRecord;
    }

    private function recordBlockedRequest(string $ip, string $url): void
    {
        try {
            dispatch(new BlackListLog($ip, $url));
        } catch (\Throwable $e) {
            RedisFailureLogger::report('blacklist logging dispatch failure', [
                'ip' => $ip,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
