<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index()
    {
        return view('welcome', ['info' => 'Hello World']);
    }

    public function test()
    {
        abort(403);
    }
}
