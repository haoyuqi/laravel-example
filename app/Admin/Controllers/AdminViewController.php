<?php

namespace App\Admin\Controllers;

use App\Models\VisitorStatistics;
use Illuminate\Support\Facades\Redis;

class AdminViewController
{
    public static function dashboardTitle()
    {
        return view('admin.title');
    }

    public static function visitsCount()
    {
        $data = [
            ['name' => '今日 UV', 'value' => Redis::scard('uv_set_'.now()->toDateString())],
            ['name' => '今日 PV', 'value' => Redis::get('pv_count_'.now()->toDateString())],
            ['name' => '昨日 UV', 'value' => VisitorStatistics::where('type', 'uv')->whereDate('date', today()->subDay())->value('count')],
            ['name' => '昨日 PV', 'value' => VisitorStatistics::where('type', 'pv')->whereDate('date', today()->subDay())->value('count')],
            ['name' => '近 5 日平均 UV', 'value' => round(VisitorStatistics::where('type', 'uv')->whereDate('date', '>=', today()->subDays(5))->avg('count'))],
            ['name' => '近 5 日平均 PV', 'value' => round(VisitorStatistics::where('type', 'pv')->whereDate('date', '>=', today()->subDays(5))->avg('count'))],
            ['name' => '近 30 日平均 UV', 'value' => round(VisitorStatistics::where('type', 'uv')->whereDate('date', '>=', today()->subDays(30))->avg('count'))],
            ['name' => '近 30 日平均 PV', 'value' => round(VisitorStatistics::where('type', 'pv')->whereDate('date', '>=', today()->subDays(30))->avg('count'))],
        ];

        return view('admin.visits-count', compact('data'));
    }
}
