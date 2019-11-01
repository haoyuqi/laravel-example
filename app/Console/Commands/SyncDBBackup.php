<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class SyncDBBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:db-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步数据库备份';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $process = new Process([
            'scp',
            '-r',
            Storage::disk(config('db-snapshots.disk'))->path(''),
            config('db-snapshots.db_backup_host')
        ]);

        $process->setTimeout(0);

        $process->run();
    }
}
