<?php

namespace App\Libraries\GetCityByIp;

use GuzzleHttp\ClientInterface;

abstract class GetCityByIpAbstract
{
    protected $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    abstract public function getCity($ip);

    protected function checkIp($ip)
    {
        return is_ip($ip);
    }
}
