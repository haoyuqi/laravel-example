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

        if (!$res) {
            return false;
        }

        dispatch(new BlackListLog($res, $url));

        return true;
    }
}
