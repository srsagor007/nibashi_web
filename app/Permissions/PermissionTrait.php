<?php

namespace App\Permissions;

use Illuminate\Support\Facades\Cache;

trait PermissionTrait
{
    public function hasPermission(string|array $permission): bool
    {
        $user = $this;
        if ($user->is_superuser) {
            return true;
        }

        $permissions = Cache::remember('user_permissions_' . $user->id, 60, function () {
            $role = session('role');

            return $role->permissions ?? collect([]);
        });

        $names = $permissions->pluck('name')->toArray();
        $slugs = $permissions->pluck('slug')->toArray();

        $all = array_merge($names, $slugs);

        if (is_array($permission)) {
            return collect($permission)->some(fn ($p) => in_array($p, $all));
        }

        return in_array($permission, $all);
    }

    public function permissions()
    {
        $role = session('role');

        return $role->permissions();
    }
}
