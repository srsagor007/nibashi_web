<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thana extends Model
{
    use SoftDeletes;
     protected $fillable = [
        'name',
        'division_id',
        'district_id',
        'is_active',
    ];
}
