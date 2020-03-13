<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckCountRequest;

class SortController extends Controller
{
    private $shuffleArr;

    public function __construct(CheckCountRequest $request)
    {
        $arr = range(1, $request->input('count'));
        shuffle($arr);

        $this->shuffleArr = $arr;
    }

    /**
     * 冒泡排序
     * @return \Illuminate\Http\JsonResponse
     */
    public function bubbleSort()
    {
        return response()->json(bubble_sort($this->shuffleArr));
    }

    /**
     * 快速排序
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickSort()
    {
        return response()->json(quick_sort($this->shuffleArr));
    }

    /**
     * 选择排序
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectSort()
    {
        return response()->json(select_sort($this->shuffleArr));
    }
}
