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
            $uvKey = 'uv_set_'.now()->toDateString();
            Redis::sadd($uvKey, $ip);

            $pvKey = 'pv_count_'.now()->toDateString();
            Redis::incr($pvKey);
        } catch (\Throwable $e) {
            RedisFailureLogger::report('pv/uv count failure', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
