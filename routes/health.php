<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

// No web/session middleware: dependency failures must not obscure probe results.
Route::get('/health/ready', function () {
    $dbOk = true;
    try {
        DB::select('SELECT 1');
    } catch (Throwable) {
        $dbOk = false;
    }

    $redisOk = true;
    try {
        Redis::ping();
    } catch (Throwable) {
        $redisOk = false;
    }

    // Avoid resolving the response factory's redirector/session dependency.
    return new JsonResponse([
        'status' => ($dbOk && $redisOk) ? 'ready' : ($dbOk ? 'degraded' : 'unhealthy'),
        'dependencies' => [
            'database' => $dbOk ? 'healthy' : 'unhealthy',
            'redis' => $redisOk ? 'healthy' : 'degraded',
        ],
    ], $dbOk ? 200 : 503);
});
