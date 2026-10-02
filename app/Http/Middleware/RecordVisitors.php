<?php

namespace App\Http\Middleware;

use App\Jobs\RecordVisitors as RecordVisitorsJob;
use App\Service\BlackListService;
use App\Support\RedisFailureLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordVisitors
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

        if (! app()->isLocal()) {
            $ip = $request->getClientIp();
            $requestUrl = $request->getRequestUri();
            $blackListService = app()->make(BlackListService::class);

            if ($blackListService->checkIp($ip, $requestUrl)) {
                abort(403);
            }

            try {
                dispatch(new RecordVisitorsJob($ip, $requestUrl));
            } catch (\Throwable $e) {
                RedisFailureLogger::report('visitor recording dispatch failure', [
                    'ip' => $ip,
                    'url' => $requestUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $next($request);
    }
}
