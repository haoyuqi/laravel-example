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

        dispatch(new \App\Jobs\RecordVisitors(get_real_ip(), $request->getRequestUri()));

        return $next($request);
    }
}
