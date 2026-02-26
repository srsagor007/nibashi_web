<?php

namespace App\Models;

use App\Traits\CommonAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use CommonAttributes;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address_line',
        'building_owner',
        'building_no',
        'division_id',
        'district_id',
        'thana_id',
        'area_id',
        'sector_node_id',
        'block_node_id',
        'road_node_id',
        'latitude',
        'longitude',
        'has_gas',
        'has_generator',
        'has_lift',
        'has_cctv',
        'has_security_guard',
        'has_parking',
        'created_by',
        'is_active',
    ];

    protected $appends = [
        'road_no',
        'block_no',
        'avenue',
    ];

    protected $casts = [
        'building_owner' => 'integer',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'has_gas' => 'boolean',
        'has_generator' => 'boolean',
        'has_lift' => 'boolean',
        'has_cctv' => 'boolean',
        'has_security_guard' => 'boolean',
        'has_parking' => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function thana(): BelongsTo
    {
        return $this->belongsTo(Thana::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function areaNode(): BelongsTo
    {
        return $this->belongsTo(AreaNode::class, 'road_node_id');
    }

    public function sectorNode(): BelongsTo
    {
        return $this->belongsTo(AreaNode::class, 'sector_node_id');
    }

    public function blockNode(): BelongsTo
    {
        return $this->belongsTo(AreaNode::class, 'block_node_id');
    }

    public function roadNode(): BelongsTo
    {
        return $this->belongsTo(AreaNode::class, 'road_node_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(BuildingImage::class)->orderBy('sort_order');
    }

    public function flats(): HasMany
    {
        return $this->hasMany(Flat::class)->latest('id');
    }

    public function getRoadNoAttribute(): ?string
    {
        return $this->roadNode?->name;
    }

    public function getBlockNoAttribute(): ?string
    {
        return $this->blockNode?->name;
    }

    public function getAvenueAttribute(): ?string
    {
        return $this->sectorNode?->name;
    }
}
