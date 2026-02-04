<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CommonAttributes;

class Division extends Model
{
    use SoftDeletes;
    use CommonAttributes;

    protected $fillable = [
        'name',
        'is_active',
    ];
}
