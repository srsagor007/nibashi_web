<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flat extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'building_id',
        'tenant_id',
        'flat_number',
        'floor_no',
        'bed_room',
        'bathroom',
        'balcony',
        'kitchen',
        'dining',
        'drawing',
        'house_rent',
        'service_charge',
        'is_furnished',
        'preferable',
        'status',
        'vacant_date',
        'total_flat_size',
        'created_by',
        'is_active',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'floor_no' => 'integer',
        'bed_room' => 'integer',
        'bathroom' => 'integer',
        'balcony' => 'integer',
        'kitchen' => 'integer',
        'dining' => 'integer',
        'drawing' => 'integer',
        'house_rent' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'is_furnished' => 'boolean',
        'vacant_date' => 'date:Y-m-d',
        'total_flat_size' => 'decimal:2',
        'created_by' => 'integer',
        'is_active' => 'boolean',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(FlatImage::class)->orderBy('sort_order');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        $image = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return data_get($image, 'image_url');
    }
}
