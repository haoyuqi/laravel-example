<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckCountRequest;
use App\Jobs\QueueJob;

class QueueController extends Controller
{
    /**
     * 创建队列
     *
     * @return string
     */
    public function create(CheckCountRequest $request)
    {
        dispatch(new QueueJob($request->input('count')));

        return view('queue.create', ['info' => 'success']);
    }
}
