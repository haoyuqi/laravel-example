<?php

namespace App\Http\Middleware;

use App\Support\RedisFailureLogger;
use App\Support\VisitorStatisticsRetention;
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
            $date = now();
            $uvKey = 'uv_set_'.$date->toDateString();
            Redis::sadd($uvKey, $ip);
            Redis::expireat($uvKey, VisitorStatisticsRetention::expiresAt($date));

            $pvKey = 'pv_count_'.$date->toDateString();
            Redis::incr($pvKey);
            Redis::expireat($pvKey, VisitorStatisticsRetention::expiresAt($date));
        } catch (\Throwable $e) {
            RedisFailureLogger::report('pv/uv count failure', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
