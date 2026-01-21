<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use CommonAttributes;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'otp',
        'secret',
        'expire_ts',
    ];
}
