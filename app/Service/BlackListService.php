<?php

namespace App\Service;

use App\Jobs\BlackListLog;
use App\Models\BlackList;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
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
            Log::warning('blacklist cache invalidation failure', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
    }

    public function touchTtl(string $key): void
    {
        try {
            Redis::expire($key, 172800);
        } catch (\Throwable $e) {
            Log::warning('blacklist cache write failure', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    public function checkIp($ip, $url)
    {
        $cache_key = $this->cacheKey();

        try {
            $cached = Redis::hget($cache_key, $ip);
        } catch (\Throwable $e) {
            Log::warning('blacklist cache read failure', ['ip' => $ip, 'error' => $e->getMessage()]);
            $cached = null;
        }

        if ($cached !== null && $cached !== false) {
            $is_black_ip = (bool) $cached;

            if ($is_black_ip) {
                dispatch(new BlackListLog($ip, $url));
            }

            return $is_black_ip;
        }

        $res = $this->blackModel->where('ip', $ip)->first();

        try {
            Redis::hset($cache_key, $ip, ($res ? 1 : 0));
            $this->touchTtl($cache_key);
        } catch (\Throwable $e) {
            Log::warning('blacklist cache write failure', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        if ($res) {
            dispatch(new BlackListLog($ip, $url));
        }

        return (bool) $res;
    }
}
