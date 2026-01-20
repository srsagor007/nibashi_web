<?php

namespace App\View\Components;

use App\Models\Menu;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class LeftNav extends Component
{
    public $menus;

    public $mm; // main menu

    public $sm; // sub menu

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->menus = $this->getMenusForUser();

        $this->mm = request()->mm ? request()->mm : session()->get('mm');
        $this->sm = request()->sm ? request()->sm : session()->get('sm');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.left-nav');
    }

    public function getMenusForUser()
    {
        $user = auth()->user();

        return Cache::remember("user_menus_{$user->id}", 60, function () use ($user) {
            $region_wise_menus = null;
            $role_slug = session()->has('role') ? session('role')->slug : null;

            if ($user->is_superuser) {
                $menus = Menu::active()->orderBy('menu_order', 'asc')->get();
            } else {
                $role = session()->has('role') ? session('role') : null;
                $menus = $role ? $role->menus()->active()->orderBy('menu_order', 'asc')->get() : [];

                if ($role && in_array($role->slug, ['rsm', 'dsm'])) {
                    $rsm_code = $user->user_code;
                    if ($role->slug === 'dsm') {
                        $rsm_code = $user->supervisor_user_code;
                    }
                    $region_wise_menus = DB::table('tbl_web_menu_access')
                        ->where('rsm_code', $rsm_code)
                        ->pluck('menu_id')
                        ->toArray();
                }
            }

            $preparedData = collect([]);
            foreach ($menus as $menu) {
                if (! $menu->parent_menu_id) {
                    $preparedData->push($menu);
                    $menu->main_menu = true;

                    $sub_menus = collect([]);
                    foreach ($menus as $sub_menu) {
                        if ($menu->id === $sub_menu->parent_menu_id) {
                            $sub_menus->push($sub_menu);
                        }
                    }
                    $menu->sub_menus = $sub_menus;
                }
            }

            $preparedData = $preparedData->filter(function ($menu) use ($region_wise_menus, $role_slug) {
                if (in_array($role_slug, ['rsm', 'dsm']) && ! in_array($menu->id, $region_wise_menus)) {
                    return false;
                }

                return true;
            });

            return $preparedData;
        });
    }
}
