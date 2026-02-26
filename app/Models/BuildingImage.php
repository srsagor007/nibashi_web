<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BuildingImage extends Model
{
    protected $fillable = [
        'building_id',
        'image_path',
        'sort_order',
    ];

    protected $appends = [
        'image_url',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }
}
