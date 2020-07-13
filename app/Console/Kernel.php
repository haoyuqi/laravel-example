<?php

namespace App\Console;

use App\Console\Commands\ClearFiles;
use App\Console\Commands\RedisForget;
use App\Console\Commands\SyncDBBackup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SyncDBBackup::class,
        ClearFiles::class,
        RedisForget::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('snapshot:create --compress')->dailyAt('00:01');
        // $schedule->command('sync:db-backup')->dailyAt('00:02');
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        $schedule->command('clear:files')->dailyAt('00:10');
        $schedule->command('redis:forget black_list_' . now()->subDay()->toDateString())->dailyAt('00:20');
        $schedule->command('telescope:prune --hours=72')->dailyAt('00:30');
        $schedule->command('redis:forget black_list_' . now()->toDateString())->twiceDaily(7, 13);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
