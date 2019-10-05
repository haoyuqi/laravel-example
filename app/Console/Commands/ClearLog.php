<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理日志';

    protected $path;

    protected $expires = 604800;// 7 天

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->path = storage_path('logs');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $logs = collect(File::files($this->path))
            ->filter(function ($file, $key) {
                return (
                    $file != '.gitignore' &&
                    time() - File::lastModified($file) > $this->expires
                );
            });

        if ($logs->isNotEmpty()) {
            File::delete($logs->toArray());
        }
    }
}
