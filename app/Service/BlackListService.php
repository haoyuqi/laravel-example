<?php

namespace App\Service;

use App\Jobs\BlackListLog;
use App\Models\BlackList;
use Illuminate\Support\Facades\Redis;

class BlackListService
{
    private $blackModel;

    public function __construct(BlackList $blackList)
    {
        $this->blackModel = $blackList;
    }

    public function checkIp($ip, $url)
    {
        $cache_key = 'black_list_' . now()->toDateString();

        if (Redis::hexists($cache_key, $ip)) {
            $is_black_ip = (bool)Redis::hget($cache_key, $ip);
            if ($is_black_ip) {
                dispatch(new BlackListLog($ip, $url));
            }
            return $is_black_ip;
        }

        $res = $this->blackModel->where('ip', $ip)->first();

        Redis::hset($cache_key, $ip, ($res ? 1 : 0));

        if ($res) {
            dispatch(new BlackListLog($ip, $url));
        }

        return (bool)$res;
    }
}
