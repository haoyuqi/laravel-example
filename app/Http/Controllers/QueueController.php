<?php

namespace App\Http\Controllers;

use App\Jobs\QueueJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QueueController extends Controller
{
    /**
     * 创建队列
     * @param Request $request
     * @return string
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'count' => 'bail|required|integer|between:1,10000'
        ]);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        dispatch(new QueueJob($request->input('count')));

        return 'success';
    }
}
