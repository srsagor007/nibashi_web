<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CommonAttributes;


class Area extends Model
{
    use SoftDeletes , CommonAttributes;
    protected $fillable = [
        'name',
        'division_id',
        'district_id',
        'thana_id',
        'is_active',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function thana()
    {
        return $this->belongsTo(Thana::class);
    }

}
