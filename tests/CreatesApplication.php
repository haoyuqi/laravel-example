<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Never share warning-throttle state with another test or the live app.
        $app['config']->set('cache.stores.redis_failure_logs.path',
            storage_path('framework/cache/testing-redis-failure-logs/'.bin2hex(random_bytes(8))));

        return $app;
    }
}
