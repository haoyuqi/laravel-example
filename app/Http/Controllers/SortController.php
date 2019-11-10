<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckCountRequest;

class SortController extends Controller
{
    public function bubbleSort(CheckCountRequest $request)
    {
        $arr = range(1, $request->input('count'));
        shuffle($arr);

        return response()->json(bubble_sort($arr));
    }
}
