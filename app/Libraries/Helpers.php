<?php
/**
 * Helpers.php
 * 辅助函数
 * Date: 2019/11/3
 */

if (!function_exists('get_real_ip')) {
    function get_real_ip()
    {
        return request()->hasHeader('X-Real-IP') ? request()->header('X-Real-IP') : request()->getClientIp();
    }
}
