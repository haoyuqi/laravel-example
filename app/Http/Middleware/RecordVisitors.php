<?php

namespace App\Http\Middleware;

use App\Service\BlackListService;
use Closure;

class RecordVisitors
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!app()->isLocal()) {
            $ip = request()->getClientIp();
            $request_url = $request->getRequestUri();
            $black_list_service = app()->make(BlackListService::class);

            if ($black_list_service->checkIp($ip, $request_url)) {
                abort(403);
            }

            dispatch(new \App\Jobs\RecordVisitors($ip, $request_url));
        }

        return $next($request);
    }
}
