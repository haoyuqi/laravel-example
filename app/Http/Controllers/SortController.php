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
        $arr = $this->getShuffleArray($request->input('count'));

        return response()->json(bubble_sort($arr));
    }

    /**
     * 快速排序
     * @param CheckCountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickSort(CheckCountRequest $request)
    {
        $arr = $this->getShuffleArray($request->input('count'));

        return response()->json(quick_sort($arr));
    }

    /**
     * 获取 shuffle 后的数组
     * @param int $count
     * @return array
     */
    protected function getShuffleArray($count)
    {
        $arr = range(1, $count);
        shuffle($arr);

        return $arr;
    }
}
