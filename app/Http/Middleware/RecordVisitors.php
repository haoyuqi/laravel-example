<?php

namespace App\Http\Middleware;

use Closure;

class RecordVisitors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ip = get_real_ip();
        $url = $request->getRequestUri();

        return $next($request);
    }
}
