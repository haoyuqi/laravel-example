<?php

namespace App\Jobs;

use App\Models\BlackList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BlackListLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 最大失败次数
    public $tries = 5;

    // 超时
    public $timeout = 120;

    protected $blackListModel;

    protected $uri;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BlackList $blackList, $uri)
    {
        $this->queue = 'black-list-log';
        $this->blackListModel = $blackList;
        $this->uri = $uri;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $black_list_log = new \App\Models\BlackListLog();
        $black_list_log->url = $this->uri;

        $this->blackListModel->logs()->save($black_list_log);
    }
}
