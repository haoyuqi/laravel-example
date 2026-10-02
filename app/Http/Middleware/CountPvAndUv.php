<?php

namespace App\Http\Middleware;

use App\Support\RedisFailureLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CountPvAndUv
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $healthPath = ltrim((string) config('app.health_path', '/up'), '/');
        if (($healthPath !== '' && $request->is($healthPath)) || $request->is('health/*')) {
            return $next($request);
        }

        $ip = $request->getClientIp();

        try {
            $uv_key = 'uv_set_'.now()->toDateString();
            Redis::sadd($uv_key, $ip);

            $pv_key = 'pv_count_'.now()->toDateString();
            Redis::incr($pv_key);
        } catch (\Throwable $e) {
            RedisFailureLogger::report('pv/uv count failure', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
