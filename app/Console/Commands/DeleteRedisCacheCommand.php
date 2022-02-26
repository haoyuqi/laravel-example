<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DeleteRedisCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:redis-cache {key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete useless redis cache.';

    protected $redis;

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
     */
    public function handle()
    {
        $key = $this->argument('key');

        if (!Redis::exists($key)) {
            $info = "`$key` does not exist.";
        } else {
            $is_success = Redis::del($key);
            $info = "`$key`" . ($is_success ? ' delete success.' : ' delete failed.');
        }

        info('Delete redis cache.');
        info($info);

        $this->info($info);
        return true;
    }
}
