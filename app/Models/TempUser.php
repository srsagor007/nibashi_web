<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempUser extends Model
{
    protected $fillable = [
        'name',
        'userid',
        'phone_number',
        'password',
        'primary_role_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
