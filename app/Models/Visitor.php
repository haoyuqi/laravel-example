<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends BaseModel
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = ['ip', 'city'];

    public static $alias = [
        'id' => 'ID',
        'ip' => 'IP',
        'city' => '城市',
        'created_at' => '添加时间',
        'updated_at' => '更新时间',
        'deleted_at' => '删除时间',
    ];

    public function logs()
    {
        return $this->hasMany(VisitorLog::class);
    }
}
