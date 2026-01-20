<?php

namespace App\Http\Controllers;

use App\Attributes\PermissionAttr;
use App\Services\UserService;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
{
    public function __construct(protected UserService $userService) {}

    #[PermissionAttr(description: 'Dashboard page')]
    public function index()
    {
        $user = auth()->user();
        $user_role = $user->user_type;
        $is_admin_user = $user->is_superuser == 1 || ($user_role && in_array($user_role->slug, ['admin', 'support']));

        return view('dashboard.index', compact('is_admin_user'));
    }

    #[PermissionAttr(description: 'Dashboard cards data ajax')]
    public function get_data()
    {
        $user = auth()->user();

        $business_code = $user->tbl_business_business_code;

        $pso_users = $this->get_user($user);

        $pso_user_codes = $pso_users->pluck('pso.user_code');

        // Total PSO Count
        $total_pso_count = $pso_user_codes->count();

        // Total Tests (already optimized)
        $total_test = DB::table('tbl_exam')
            ->when($business_code && $business_code !== '00', function ($q) use ($business_code) {
                $q->where('tbl_business_business_code', $business_code);
            })
            ->distinct()
            ->count('exam_id');

        // Total Doctors
        $total_doctor = DB::table('tbl_pso_wise_doctor as pwd')
            ->join('tbl_doctor_info as a', 'a.id', '=', 'pwd.tbl_doctor_info_id')
            ->when(! $user->isAdmin(), function ($q) use ($pso_user_codes) {
                $q->whereIn('pwd.user_code', $pso_user_codes);
            })
            ->where('a.approve_status', 2)
            ->where('a.deleted_status', 0)
            ->distinct()
            ->count('a.id');

        // Total DCR logs
        $total_dcr = DB::table('tbl_dcr_logs as dl')
            ->when(! $user->isAdmin(), function ($q) use ($pso_user_codes) {
                $q->whereIn('dl.user_code', $pso_user_codes);
            })
            ->distinct()
            ->count('dl.id');

        return response()->json([
            'total_pso_count' => $total_pso_count,
            'total_test' => $total_test,
            'total_doctor' => $total_doctor,
            'total_dcr' => $total_dcr,
        ]);

    }

    #[PermissionAttr(description: 'Dashboard exam stats data ajax')]
    public function exam_statistics()
    {
        $user = auth()->user();

        $year = request()->input('year') ?? date('Y');
        $business_code = session('business_code');

        $pso_users = $this->get_user($user);
        // Get user_code list efficiently
        $pso_ids = $pso_users->pluck('pso.id');

        $query = DB::table('tbl_exam_assign as ea')
            ->join('tbl_exam as e', 'e.exam_id', '=', 'ea.tbl_exam_exam_id')
            ->selectRaw('
                MONTH(ea.date) as month,
                COUNT(CASE WHEN ea.exam_status = 1 THEN 1 END) AS plus,
                COUNT(CASE WHEN e.pass_marks <= (ea.marks * 100) / e.exam_marks AND ea.exam_status = 1 THEN ea.assign_id END) AS total_pass,
                COUNT(CASE WHEN e.pass_marks > (ea.marks * 100) / e.exam_marks AND ea.exam_status = 1 THEN ea.assign_id END) AS total_fail
            ')
            ->whereYear('ea.date', $year)
            ->whereIn('ea.user_id', $pso_ids);

        if ($business_code && $business_code != '00') {
            $query->where('e.tbl_business_business_code', $business_code);
        }

        $query->groupBy(DB::raw('MONTH(ea.date)'));

        $results = $query->get();

        $data = [];

        foreach ($results as $exam) {
            $month_name = Carbon::createFromDate($year, $exam->month, 1)->format('F');

            $plus = (int) $exam->plus;

            $pass = $plus > 0 ? round(($exam->total_pass * 100) / $plus) : 0;
            $fail = $plus > 0 ? round(($exam->total_fail * 100) / $plus) : 0;

            $data[] = [
                'state' => $month_name,
                'pass' => $pass,
                'fail' => $fail,
            ];
        }

        return response()->json($data);
    }

    #[PermissionAttr(description: 'Required for dashboard cards data')]
    public function get_user($user)
    {
        $user_role = $user->user_type;
        $business_code = $user->tbl_business_business_code;

        $pso_role_id = DB::table('roles')->where('slug', 'pso')->value('id');

        $pso_users = DB::table('users as pso')
            ->where('pso.primary_role_id', $pso_role_id)
            ->where('pso.is_active', 1)
            ->whereNull('pso.deleted_at');

        if (isset($user_role->slug)) {
            if ($user_role->slug === 'dsm') {
                $pso_users->where('pso.supervisor_user_code', $user->user_code)
                    ->where('pso.tbl_business_business_code', $business_code);
            } elseif ($user_role->slug === 'rsm') {
                $pso_users->join('users as dsm', 'pso.supervisor_user_code', '=', 'dsm.user_code')
                    ->where('dsm.supervisor_user_code', $user->user_code)
                    ->where('dsm.tbl_business_business_code', $business_code)
                    ->where('dsm.is_active', 1)
                    ->whereNull('dsm.deleted_at');
            } elseif ($user_role->slug === 'sm') {
                $pso_users->join('users as dsm', 'pso.supervisor_user_code', '=', 'dsm.user_code')
                    ->join('users as rsm', 'dsm.supervisor_user_code', '=', 'rsm.user_code')
                    ->where('rsm.supervisor_user_code', $user->user_code)
                    ->where('rsm.tbl_business_business_code', $business_code)
                    ->where('dsm.is_active', 1)
                    ->where('rsm.is_active', 1)
                    ->whereNull('dsm.deleted_at')
                    ->whereNull('rsm.deleted_at');
            }
        }

        return $pso_users;

    }
}
