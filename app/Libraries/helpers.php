<?php
/**
 * Helpers.php
 * 辅助函数
 * Date: 2019/11/3
 */

use \Illuminate\Support\Facades\Validator;

if (!function_exists('get_real_ip')) {
    /**
     * 获取 IP
     * @param string $header
     * @return array|string|null
     */
    function get_real_ip($header = 'X-Real-IP')
    {
        return request()->hasHeader($header) ? request()->header($header) : request()->getClientIp();
    }
}

if (!function_exists('is_ip')) {
    function is_ip($ip)
    {
        return Validator::make(['ip' => $ip], [
            'ip' => 'required|ip',
        ])->passes();
    }
}
