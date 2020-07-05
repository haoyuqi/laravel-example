<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlackListLog extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public static $alias = [
        'id' => 'ID',
        'url' => 'URL',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ];

    public function blackList()
    {
        return $this->belongsTo(BlackList::class);
    }
}
