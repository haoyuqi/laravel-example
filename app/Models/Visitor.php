<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function logs()
    {
        return $this->hasMany(VisitorLog::class);
    }
}
