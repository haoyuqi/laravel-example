<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitorLog extends BaseModel
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }
}
