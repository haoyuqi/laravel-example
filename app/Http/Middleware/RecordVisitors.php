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
            $request_url = $request->getRequestUri();
            $black_list_service = app()->make(BlackListService::class);

            if ($black_list_service->checkIp($ip, $request_url)) {
                abort(403);
            }

            try {
                dispatch(new RecordVisitorsJob($ip, $request_url));
            } catch (\Throwable $e) {
                RedisFailureLogger::report('visitor recording dispatch failure', [
                    'ip' => $ip,
                    'url' => $request_url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $next($request);
    }
}
