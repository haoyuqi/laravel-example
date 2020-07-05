<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlackList extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public static $alias = [
        'id' => 'ID',
        'ip' => 'IP',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
        'deleted_at' => '删除时间',
    ];

    public function logs()
    {
        return $this->hasMany(BlackListLog::class);
    }
}
