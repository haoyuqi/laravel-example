<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = ['ip', 'city'];

    public static $alias = [
        'city' => '城市',
    ];

    public function logs()
    {
        return $this->hasMany(VisitorLog::class);
    }
}
