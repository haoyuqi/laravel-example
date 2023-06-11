<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CountPvAndUv
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->getClientIp();

        $uv_key = 'uv_set_'.now()->toDateString();
        Redis::sadd($uv_key, $ip);

        $pv_key = 'pv_count_'.now()->toDateString();
        Redis::incr($pv_key);

        return $next($request);
    }
}
