<?php

namespace App\Services;

use App\Models\DcrLog;
use App\Models\Role;
use App\Models\Rx;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function business_wise_users($business_code, $role_id)
    {
        return User::query()
            ->with('region')
            ->where('primary_role_id', $role_id)
            ->where('tbl_business_business_code', $business_code)
            ->select('id', 'name', 'userid', 'user_code', 'supervisor_user_code', 'rsm_region_id', 'primary_role_id', 'tbl_business_business_code', 'hq')
            ->get();
    }

    public function role_wise_users($role_slug, $supervisor_user_code = null)
    {
        $role = Role::query()
            ->where('slug', $role_slug)
            ->first();

        return User::query()
            ->with('depot:id,depot_code,depot_name,depot_address')
            ->when($supervisor_user_code, function ($query) use ($supervisor_user_code) {
                if (is_array($supervisor_user_code)) {
                    return $query->whereIn('supervisor_user_code', $supervisor_user_code);
                } else {
                    return $query->where('supervisor_user_code', $supervisor_user_code);
                }
            })
        // ->where('supervisor_user_code', $supervisor_user_code)
            ->where('primary_role_id', $role->id)
            ->select('id', 'name', 'userid', 'user_code', 'supervisor_user_code', 'rsm_region_id', 'primary_role_id', 'tbl_business_business_code', 'hq', 'tbl_depot_id', 'rsm_region')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function role_wise_assigned_users($role_slug, $supervisor_user_code = null)
    {
        $role = null;
        if ($role_slug === 'sm') {
            $role = Role::query()
                ->where('slug', 'rsm')
                ->first();

        } elseif ($role_slug === 'rsm') {
            $role = Role::query()
                ->where('slug', 'dsm')
                ->first();

        } elseif ($role_slug === 'dsm') {
            $role = Role::query()
                ->where('slug', 'pso')
                ->first();
        }

        return User::query()
            ->with('depot:id,depot_code,depot_name,depot_address')
            ->when($supervisor_user_code, function ($query) use ($supervisor_user_code) {
                if (is_array($supervisor_user_code)) {
                    return $query->whereIn('supervisor_user_code', $supervisor_user_code);
                } else {
                    return $query->where('supervisor_user_code', $supervisor_user_code);
                }
            })
        // ->where('supervisor_user_code', $supervisor_user_code)
            ->when($role, function ($query) use ($role) {
                return $query->where('primary_role_id', $role->id);
            })
            // ->where('primary_role_id', $role->id)
            ->select('id', 'name', 'userid', 'user_code', 'supervisor_user_code', 'rsm_region_id', 'primary_role_id', 'tbl_business_business_code', 'hq', 'tbl_depot_id')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function pso_wise_doctors($pso_code)
    {
        $pso = User::where('user_code', $pso_code)->first();
        $doctors = $pso->pso_doctors()->where('approve_status', 2)
            ->select('tbl_doctor_info.id', 'tbl_doctor_info.doctor_id', 'doctor_name', 'market_name', 'tbl_doctor_info.tbl_dcr_doctor_type_id')
            ->get();

        return $doctors;
    }

    public function getUsers(array $filters)
    {
        $userType = $filters['user_type'] ?? null;
        $regionIds = $filters['region_ids'] ?? [];
        $businessIds = $filters['business_ids'] ?? [];
        $psoTypes = $filters['pso_types'] ?? [];
        $target_users = $filters['target_users'] ?? [];

        $users = User::query();

        if ($userType == 'universal') {
            $users->whereIn('primary_role_id', [9, 6, 5]);
        } else {
            $users->where('primary_role_id', $userType);
        }

        // Region-wise filtering
        if (! empty($regionIds)) {
            if ($userType == 9) { // PSO
                /* $users->join('users as dsm', 'users.supervisor_user_code', '=', 'dsm.user_code')
                    ->join('users as rsm', 'dsm.supervisor_user_code', '=', 'rsm.user_code')
                    ->whereIn('rsm.rsm_region_id', $regionIds); */
                $rsmList = User::whereIn('rsm_region_id', $regionIds)->pluck('user_code')->toArray();
                $dsmList = User::whereIn('supervisor_user_code', $rsmList)->pluck('user_code')->toArray();
                $users->whereIn('supervisor_user_code', $dsmList);
            } elseif ($userType == 6) { // DSM
                $rsmList = User::whereIn('rsm_region_id', $regionIds)->pluck('user_code')->toArray();
                $users->whereIn('supervisor_user_code', $rsmList);
            } elseif ($userType == 5) { // RSM
                $users->whereIn('rsm_region_id', $regionIds);
            }
        }

        // Business-wise filtering
        if (! empty($businessIds)) {
            $users->whereIn('tbl_business_business_code', $businessIds);
        }

        if (! empty($target_users)) {
            $users->whereIn('id', $target_users);
        }

        // PSO Type filtering
        if ($userType == 9 && ! empty($psoTypes)) {
            $users->whereIn('tbl_pso_user_type_id', $psoTypes);
        }

        return $users->select('id', 'name', 'userid', 'user_code', 'device_token')->orderBy('name')->get();
    }

    public function get_role_wise_psos_id($user)
    {
        if (! $user->user_type) {
            return collect();
        }

        if ($user->is_superuser) {
            return collect();
        }

        $pso_role = Role::where('slug', 'pso')->first();

        return DB::table('users as pso')
            ->when($user->user_type && $user->user_type->slug == 'pso', function ($query) use ($user) {
                return $query->where('pso.id', $user->id);
            })
            ->when($user->user_type && $user->user_type->slug == 'dsm', function ($query) use ($user) {
                return $query->where('pso.supervisor_user_code', $user->user_code)
                    ->where('pso.tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->when($user->user_type && $user->user_type->slug == 'rsm', function ($query) use ($user) {
                return $query->join('users as dsm', 'pso.supervisor_user_code', '=', 'dsm.user_code')
                    ->where('dsm.supervisor_user_code', $user->user_code)
                    ->where('dsm.tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->when($user->user_type && $user->user_type->slug == 'sm', function ($query) use ($user) {
                return $query->join('users as dsm', 'pso.supervisor_user_code', '=', 'dsm.user_code')
                    ->join('users as rsm', 'dsm.supervisor_user_code', '=', 'rsm.user_code')
                    ->where('rsm.supervisor_user_code', $user->user_code)
                    ->where('rsm.tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->where('pso.primary_role_id', $pso_role->id)
            ->where('pso.is_active', 1)
            ->pluck('pso.id');
    }

    public function get_role_wise_psos($user, $request = null)
    {
        if (! $user->user_type) {
            return collect();
        }

        if ($user->is_superuser) {
            return collect();
        }

        $pso_role = Role::where('slug', 'pso')->first();

        return User::with('dsm')
            ->where('primary_role_id', $pso_role->id)
            ->active()
            ->when($user->user_type && $user->user_type->slug == 'pso', function ($query) use ($user) {
                return $query->where('id', $user->id);
            })
            ->when($user->user_type && $user->user_type->slug == 'dsm', function ($query) use ($user) {
                return $query->where('supervisor_user_code', $user->user_code)
                    ->where('tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->when($user->user_type && $user->user_type->slug == 'rsm', function ($query) use ($user) {
                return $query->whereHas('dsm', function ($q) use ($user) {
                    $q->where('supervisor_user_code', $user->user_code)
                        ->where('tbl_business_business_code', $user->tbl_business_business_code);
                });
            })
            ->when($user->user_type && $user->user_type->slug == 'sm', function ($query) use ($user) {
                return $query->whereHas('dsm.rsm', function ($q) use ($user) {
                    $q->where('supervisor_user_code', $user->user_code)
                        ->where('tbl_business_business_code', $user->tbl_business_business_code);
                });
            })
            ->when($request, function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            })
            ->get();
    }

    public function get_role_wise_psos_code($user)
    {
        if (! $user->user_type) {
            return collect();
        }

        if ($user->is_superuser) {
            return collect();
        }

        $pso_role = Role::where('slug', 'pso')->first();

        return DB::table('users as pso')
            ->when($user->user_type && $user->user_type->slug == 'pso', function ($query) use ($user) {
                return $query->where('pso.id', $user->id);
            })
            ->when($user->user_type && $user->user_type->slug == 'dsm', function ($query) use ($user) {
                return $query->where('pso.supervisor_user_code', $user->user_code)
                    ->where('pso.tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->when($user->user_type && $user->user_type->slug == 'rsm', function ($query) use ($user) {
                return $query->join('users as dsm', 'pso.supervisor_user_code', '=', 'dsm.user_code')
                    ->where('dsm.supervisor_user_code', $user->user_code)
                    ->where('dsm.tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->when($user->user_type && $user->user_type->slug == 'sm', function ($query) use ($user) {
                return $query->join('users as dsm', 'pso.supervisor_user_code', '=', 'dsm.user_code')
                    ->join('users as rsm', 'dsm.supervisor_user_code', '=', 'rsm.user_code')
                    ->where('rsm.supervisor_user_code', $user->user_code)
                    ->where('rsm.tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->where('pso.primary_role_id', $pso_role->id)
            ->where('pso.is_active', 1)
            ->pluck('pso.user_code');
    }

    public function get_role_wise_psos_list($business_code, $role_slug, $user_code = null, $dsm_user_details = null, $rsm_user_details = null, $pso_type = null)
    {
        $role = Role::where('slug', $role_slug)->first();

        return DB::table('users as pso')
            ->join('users as dsm', function ($join) use ($dsm_user_details) {
                $join->on('pso.supervisor_user_code', '=', 'dsm.user_code')
                    ->when($dsm_user_details, function ($query) use ($dsm_user_details) {
                        $query->where('dsm.primary_role_id', '=', $dsm_user_details->primary_role_id);
                    });
            })
            ->join('users as rsm', function ($join) use ($rsm_user_details) {
                $join->on('dsm.supervisor_user_code', '=', 'rsm.user_code')
                    ->when($rsm_user_details, function ($query) use ($rsm_user_details) {
                        $query->where('rsm.primary_role_id', '=', $rsm_user_details->primary_role_id);
                    });
            })
            ->leftJoin('tbl_depot as depot', 'pso.tbl_depot_id', '=', 'depot.id') // Join depot
            ->leftJoin('tbl_pso_user_type as pso_type', 'pso.tbl_pso_user_type_id', '=', 'pso_type.id') // Join pso user type
            ->select(
                'pso.*',
                'dsm.name as dsm_name',
                'dsm.user_code as dsm_user_code',
                'dsm.primary_role_id',
                'rsm.name as rsm_name',
                'rsm.user_code as rsm_user_code',
                'rsm.primary_role_id',
                'depot.depot_name',
                'pso_type.pso_user_type_name',
            )
            ->when($user_code, function ($query) use ($business_code, $user_code, $role) {
                return $query->where('pso.user_code', $user_code)
                    ->where('pso.tbl_business_business_code', $business_code)
                    ->when($role, function ($query) use ($role) {
                        return $query->where('pso.primary_role_id', $role->id);
                    });
            })
            ->when($dsm_user_details, function ($query) use ($business_code, $dsm_user_details) {
                return $query->where('dsm.user_code', $dsm_user_details->user_code)
                    ->where('rsm.tbl_business_business_code', $business_code);
            })
            ->when($rsm_user_details, function ($query) use ($business_code, $rsm_user_details) {
                return $query->where('rsm.user_code', $rsm_user_details->user_code)
                    ->where('rsm.tbl_business_business_code', $business_code);
            })
            ->when($pso_type, function ($query) use ($pso_type) {
                return $query->where('pso.tbl_pso_user_type_id', $pso_type);
            })
            ->where('pso.primary_role_id', $role->id)
            ->where('pso.is_active', 1)
            ->get();
    }

    public function detect_primary_role_by_user_code($user_code)
    {
        return DB::table('users')
            ->where('user_code', $user_code)
            ->orderByDesc('id')
            ->first(['id', 'user_code', 'primary_role_id', 'supervisor_user_code']);
    }

    public function get_role_wise_psos_code_old($user)
    {
        $user_code = $user->user_code;
        $business_code = $user->tbl_business_business_code;

        if (! $user->user_type) {
            return collect();
        }

        switch ($user->user_type->slug) {
            case 'pso':
                return collect([$user_code]);

            case 'dsm':
                return User::where('supervisor_user_code', $user_code)->where('tbl_business_business_code', $business_code)->pluck('user_code');

            case 'rsm':
                $dsm_codes = User::where('supervisor_user_code', $user_code)->where('tbl_business_business_code', $business_code)->pluck('user_code');

                return User::whereIn('supervisor_user_code', $dsm_codes)->pluck('user_code');

            case 'sm':
                $rsm_codes = User::where('supervisor_user_code', $user_code)->where('tbl_business_business_code', $business_code)->pluck('id');

                $dsm_codes = User::whereIn('supervisor_user_code', $rsm_codes)->pluck('id');

                return User::whereIn('supervisor_user_code', $dsm_codes)->pluck('id');

            default:
                return collect();
        }
    }

    public function get_rx_count_for_user_old($user_code = null)
    {
        // Current month date range
        $current_month_start = Carbon::now()->startOfMonth();
        $current_month_end = Carbon::now()->endOfMonth();

        // Previous month date range
        $previous_month_start = Carbon::now()->subMonth()->startOfMonth();
        $previous_month_end = Carbon::now()->subMonth()->endOfMonth();

        // Get total Rx count for current month
        $total_rx_current_month = Rx::query()
            ->when($user_code, function ($query, $user_code) {
                return $query->where('submitted_by', $user_code);
            })
            ->whereBetween('submitted_on', [
                $current_month_start->startOfDay(),
                $current_month_end->endOfDay(),
            ])
            ->count();

        $total_rx_previous_month = Rx::query()
            ->when($user_code, function ($query, $user_code) {
                return $query->where('submitted_by', $user_code);
            })
            ->whereBetween('submitted_on', [
                $previous_month_start->startOfDay(),
                $previous_month_end->endOfDay(),
            ])
            ->count();

        return [
            'rx_current_month' => $total_rx_current_month,
            'rx_previous_month' => $total_rx_previous_month,
        ];
    }

    public function get_rx_count_for_user($user_code = null, $pso_user_codes = null)
    {
        $current_month_start = Carbon::now()->startOfMonth();
        $current_month_end = Carbon::now()->endOfMonth();
        $previous_month_start = Carbon::now()->subMonth()->startOfMonth();
        $previous_month_end = Carbon::now()->subMonth()->endOfMonth();

        $query = Rx::query();

        if (! empty($pso_user_codes)) {
            $query->whereIn('submitted_by', $pso_user_codes);
        } elseif ($user_code) {
            $query->where('submitted_by', $user_code);
        }

        $rx_counts = $query
            ->select(
                'submitted_by',
                DB::raw("SUM(CASE WHEN submitted_on BETWEEN '{$current_month_start}' AND '{$current_month_end}' THEN 1 ELSE 0 END) as rx_current_month"),
                DB::raw("SUM(CASE WHEN submitted_on BETWEEN '{$previous_month_start}' AND '{$previous_month_end}' THEN 1 ELSE 0 END) as rx_previous_month")
            )
            ->groupBy('submitted_by')
            ->get()
            ->keyBy('submitted_by');

        return $rx_counts;
    }

    public function get_dcr_log_count_for_user($user_code = null, $pso_user_codes = null)
    {
        $current_month_start = Carbon::now()->startOfMonth();
        $current_month_end = Carbon::now()->endOfMonth();
        $previous_month_start = Carbon::now()->subMonth()->startOfMonth();
        $previous_month_end = Carbon::now()->subMonth()->endOfMonth();

        $query = DcrLog::query();

        if (! empty($pso_user_codes)) {
            $query->whereIn('user_code', $pso_user_codes);
        } elseif ($user_code) {
            $query->where('user_code', $user_code);
        }

        $dcr_counts = $query
            ->select(
                'user_code',
                DB::raw("SUM(CASE WHEN visit_date_time BETWEEN '{$current_month_start}' AND '{$current_month_end}' THEN 1 ELSE 0 END) as dcr_current_month"),
                DB::raw("SUM(CASE WHEN visit_date_time BETWEEN '{$previous_month_start}' AND '{$previous_month_end}' THEN 1 ELSE 0 END) as dcr_previous_month")
            )
            ->groupBy('user_code')
            ->get()
            ->keyBy('user_code');

        return $dcr_counts;

        // $current_month_count = DcrLog::query()
        //     ->when($user_code, function ($query, $user_code) {
        //         return $query->where('user_code', $user_code);
        //     })
        //     ->whereBetween('visit_date_time', [$current_month_start, $current_month_end])
        //     ->count();

        // $previous_month_count = DcrLog::query()
        //     ->when($user_code, function ($query, $user_code) {
        //         return $query->where('user_code', $user_code);
        //     })
        //     ->whereBetween('visit_date_time', [$previous_month_start, $previous_month_end])
        //     ->count();

        // return [
        //     'dcr_current_month' => $current_month_count,
        //     'dcr_previous_month' => $previous_month_count,
        // ];
    }

    public function get_psos($user, $request = null)
    {
        $pso_role = Role::where('slug', 'pso')->first();

        return User::with('dsm')
            ->where('primary_role_id', $pso_role->id)
            ->active()
            ->when($user->user_type && $user->user_type->slug == 'pso', function ($query) use ($user) {
                return $query->where('id', $user->id);
            })
            ->when($user->user_type && $user->user_type->slug == 'dsm', function ($query) use ($user) {
                return $query->where('supervisor_user_code', $user->user_code)
                    ->where('tbl_business_business_code', $user->tbl_business_business_code);
            })
            ->when($user->user_type && $user->user_type->slug == 'rsm', function ($query) use ($user) {
                return $query->whereHas('dsm', function ($q) use ($user) {
                    $q->where('supervisor_user_code', $user->user_code)
                        ->where('tbl_business_business_code', $user->tbl_business_business_code);
                });
            })
            ->when($user->user_type && $user->user_type->slug == 'sm', function ($query) use ($user) {
                return $query->whereHas('dsm.rsm', function ($q) use ($user) {
                    $q->where('supervisor_user_code', $user->user_code)
                        ->where('tbl_business_business_code', $user->tbl_business_business_code);
                });
            })
            ->when($request, function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            })
            ->get();
    }
}
