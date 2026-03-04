<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoinPack extends Model
{
    use CommonAttributes;
    use SoftDeletes;

    protected $fillable = [
        'coins',
        'price',
        'badge_text',
        'badge_color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'coins' => 'integer',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}

