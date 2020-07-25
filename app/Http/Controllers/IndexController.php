<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index()
    {
        return view('index.welcome', ['info' => 'Hello World']);
    }

    public function error()
    {
        return view('index.error', ['info' => 'No Message']);
    }

    public function time()
    {
        return view('index.time', ['init_data' => [now()->toDateTimeString()]]);
    }

    public function test()
    {
        abort(403);
    }
}
