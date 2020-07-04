<?php

namespace App\Service;

use App\Jobs\BlackListLog;
use App\Models\BlackList;

class BlackListService
{
    private $blackModel;

    public function __construct(BlackList $blackList)
    {
        $this->blackModel = $blackList;
    }

    public function checkIp($ip, $url)
    {
        $res = $this->blackModel->where('ip', $ip)->first();

        // 使用 redis 换成
        // 传入 id 到队列中
        // laravel-admin 模块进行管理
        if (!$res) {
            return false;
        }

        dispatch(new BlackListLog($res, $url));

        return true;
    }
}
