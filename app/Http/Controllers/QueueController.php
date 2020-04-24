<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckCountRequest;
use App\Jobs\QueueJob;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    /**
     * 创建队列
     * @param CheckCountRequest $request
     * @return string
     */
    public function create(CheckCountRequest $request)
    {
        dispatch(new QueueJob($request->input('count')))->onQueue('queue-test');

        return view('queue.create', ['info' => 'success']);
    }
}
