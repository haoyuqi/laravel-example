<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RedisForget extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:forget {key} {connection?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除 Redis key';

    private $redis;

    private $redisConnection = 'cache';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Redis $redis)
    {
        parent::__construct();
        $this->redis = $redis;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $is_del = $this->redis::connection(
            $this->argument('connection') ?? $this->redisConnection
        )->del($this->argument('key'));

        $info = $this->argument('key') . ($is_del ? ' 删除成功' : ' 删除失败');

        info('redis forget');
        info($info);
        $this->info($info);
    }
}
