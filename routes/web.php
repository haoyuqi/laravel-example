<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SortController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index']);
Route::get('/error', [IndexController::class, 'error']);
Route::get('/test', [IndexController::class, 'test']);
Route::get('/time', [IndexController::class, 'time']);

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

    $status = ($dbOk && $redisOk) ? 'ready' : ($dbOk ? 'degraded' : 'unhealthy');
    $httpStatus = $dbOk ? 200 : 503;

    return response()->json([
        'status' => $status,
        'dependencies' => [
            'database' => $dbOk ? 'healthy' : 'unhealthy',
            'redis' => $redisOk ? 'healthy' : 'degraded',
        ],
    ], $httpStatus);
});

// queue test
Route::prefix('queue')->group(function () {
    Route::get('/create', [QueueController::class, 'create']);
});

// sort
Route::prefix('sort')->group(function () {
    Route::get('/bubble', [SortController::class, 'bubbleSort']);
    Route::get('/quick', [SortController::class, 'quickSort']);
    Route::get('/select', [SortController::class, 'selectSort']);
    Route::get('/insert', [SortController::class, 'insertSort']);
});
