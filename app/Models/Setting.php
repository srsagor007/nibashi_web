<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'name',
        'locked',
        'payload',
    ];

    protected $casts = [
        'payload' => 'string', // Assuming the value is stored as JSON
    ];

    public static function getGroup(string $group): array
    {
        return self::where('group', $group)->pluck('payload', 'name')->map(fn ($v) => json_decode($v, true))->toArray();
    }

    public static function setSetting(string $group, string $name, mixed $value): void
    {
        self::updateOrCreate(
            ['group' => $group, 'name' => $name],
            ['payload' => json_encode($value)]
        );

        // Optionally clear cache
        \Cache::forget("settings.{$group}");
    }
}
