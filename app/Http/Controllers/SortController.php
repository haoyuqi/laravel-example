<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckCountRequest;

class SortController extends Controller
{
    /**
     * 冒泡排序
     * @param CheckCountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bubbleSort(CheckCountRequest $request)
    {
        $arr = range(1, $request->input('count'));
        shuffle($arr);

        return response()->json(bubble_sort($arr));
    }
}
