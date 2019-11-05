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

if (!function_exists('bubble_sort')) {
    function bubble_sort(array $arr)
    {
        $count = count($arr);

        if ($count < 2) {
            return $arr;
        }

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($arr[$i] > $arr[$j]) {
                    $temp = $arr[$j];
                    $arr[$j] = $arr[$i];
                    $arr[$i] = $temp;
                }
            }
        }

        return $arr;
    }
}
