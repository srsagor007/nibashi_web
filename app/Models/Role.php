<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use CommonAttributes;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['title', 'slug'];

    public function menus()
    {
        return $this->belongsToMany(Menu::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
