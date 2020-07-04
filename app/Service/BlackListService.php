<?php

namespace App\Service;

use App\Models\BlackList;

class BlackListService
{
    private $blackModel;

    public function __construct(BlackList $blackList)
    {
        $this->blackModel = $blackList;
    }

    public function checkIp($ip, $uri)
    {
        $res = $this->blackModel->where('ip', $ip)->first();

        if (!$res) return false;

        return true;
    }
}
