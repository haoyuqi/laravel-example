<?php

namespace App\Admin\Controllers;

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
            ['name' => 'UV', 'value' => Redis::scard('uv_set_' . now()->toDateString())],
            ['name' => 'PV', 'value' => Redis::get('pv_count_' . now()->toDateString())],

        ];;
        return view('admin.visits-count', compact('data'));
    }
}
