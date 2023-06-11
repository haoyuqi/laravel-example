<?php

namespace App\Libraries\GetCityByIp;

use Illuminate\Support\Facades\Log;

class GeoIP extends GetCityByIpAbstract
{
    public function getCity($ip)
    {
        if (! $this->checkIp($ip)) {
            Log::error('Ip 地址错误。');
            Log::error($ip);

            return '';
        }

        $result = geoip($ip)->toArray();
        if (! array_has($result, 'city') || blank(array_get($result, 'city'))) {
            Log::error('获取数据错误。');
            Log::error($ip);
            Log::error($result);

            return '';
        }

        return array_get($result, 'city');
    }
}
