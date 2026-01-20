<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use CommonAttributes, SoftDeletes;

    protected $fillable = ['name', 'slug', 'is_active','region_id'];

    public function region(){
        return $this->belongsTo(Region::class);
    }

    public function territories()
    {
        return $this->hasMany(Territory::class);
    }

    public function distributors()
    {
        return $this->hasMany(Distributor::class);
    }
}
