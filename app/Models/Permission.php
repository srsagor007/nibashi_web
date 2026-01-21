<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use CommonAttributes;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'controller', 'is_active', 'description'];

    protected $appends = ['controller_name'];

    public function getControllerNameAttribute()
    {
        return \Str::of($this->name)->explode('@')[0];
    }
}
