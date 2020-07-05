<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlackListLog extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public static $alias = [
        'url' => 'URL',
        'created_at' => '创建时间',
    ];

    public function blackList()
    {
        return $this->belongsTo(BlackList::class);
    }
}
