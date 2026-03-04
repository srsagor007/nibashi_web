<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantFlatRentRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'flat_id',
        'building_id',
        'request_date',
        'status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'flat_id' => 'integer',
        'building_id' => 'integer',
        'request_date' => 'date:Y-m-d',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function flat()
    {
        return $this->belongsTo(Flat::class, 'flat_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }
}

