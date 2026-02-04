<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CommonAttributes;

class District extends Model
{
    use SoftDeletes;
    use CommonAttributes;
    
    protected $fillable = [
        'name',
        'division_id',
        'is_active',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

}
