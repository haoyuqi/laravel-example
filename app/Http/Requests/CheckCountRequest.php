<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckCountRequest extends FormRequest
{
    // 跳转到 error 页面
    protected $redirectAction = 'IndexController@error';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'count' => 'bail|required|integer|between:1,10000',
        ];
    }
}
