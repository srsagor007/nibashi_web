<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Imports\UsersImport;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function __construct(protected FileUploadService $fileUploadService, protected UserService $userService) {}

    public function index()
    {
        if (request()->wantsJson()) {
            $pso_role = Role::where('slug', 'pso')->first();
            $users = User::query()
                ->with([
                    'user_type:id,title,slug',
                    'rsm:id,name,userid,user_code,supervisor_user_code,rsm_region', // RSM
                    'sm:id,name,userid,user_code,supervisor_user_code',             // SM
                    'region:id,name',
                ])
                ->when(request()->role_id, function ($q) {
                    $q->where('primary_role_id', request()->role_id);
                })
                ->where('primary_role_id', '!=', $pso_role->id) // exclude PSO
                ->staff();

            return DataTables::of($users)
                ->addIndexColumn()
                /* ->editColumn('_photo', function ($row) {
                    $photo_url = $row->photo_url ?: asset('img/blank-avatar-profile.webp');

                    return '<img src="' . $photo_url . '" style="width:40px; height:40px; border-radius:50%; object-fit:cover;" alt="Photo">';
                }) */
                ->editColumn('name', function ($row) {
                    return "<a href='#' class='text-primary text-hover-gray-700 fs-6 fw-bold'>{$row->name}</a>";
                })
                ->addColumn('role', fn ($row) => $row->user_type ? $row->user_type->title : '-')
                ->addColumn('supervisor', function ($row) {
                    if ($row->user_type->slug == 'dsm') {
                        return $row->rsm ? "RSM code: {$row->rsm->user_code}" : '-';
                    } elseif ($row->user_type->slug == 'rsm') {
                        return $row->sm ? "SM code: {$row->sm->user_code}" : '-';
                    }

                    return '-';
                })
                ->addColumn('_rsm_region', fn ($row) => $row->region ? $row->region->name : '-')
                ->editColumn('status', function ($row) {
                    return $row->toggleButton(route('users.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $btn = '';

                    $btn .= '<a href="' . route('users.password-reset', ['user' => $row->id]) . '"
                        title="Reset Password"
                        onClick="return confirmPasswordReset()"
                        class="btn btn-light-success btn-icon btn-sm me-2"><i class="fas fa-key"></i></a>';

                    $btn .= $row->editButton(route('users.edit', ['user' => $row->id]));
                    $btn .= $row->deleteButton(route('users.destroy', ['user' => $row->id]));

                    return $btn;
                })
                ->editColumn('last_login', function ($row) {
                    return $row->last_login ? $row->last_login->format('d M Y h:i a') : '-';
                })
                ->rawColumns(['status', 'action', 'photo', 'name', 'jobs'])
                ->make(true);
        }

        $roles = Role::active()
            ->where('slug', '!=', 'pso')
            ->get();

        return view('users.index', compact('roles'));
    }

    public function create()
    {
        $roles = Role::active()
            ->where('slug', '!=', 'pso')
            ->get();
        $business_units = Business::active()->get();
        $depots = Depot::active()->get();
        $pso_user_types = PsoUserType::active()->get();
        $regions = Region::active()->get();

        return view('users.create', compact('roles', 'business_units', 'depots', 'pso_user_types', 'regions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:150',
            'designation' => 'required|max:200',
            'userid' => 'required|max:7|unique:' . User::class,
            'phone' => ['required', 'regex:/^01[0-9]{9}$/'],
            'tbl_business_business_code' => 'required|max:2',
            'primary_role_id' => 'required|integer',
            'tbl_depot_id' => 'nullable',
            'user_code' => [
                'required_if:primary_role_id,4,5,6',
                function ($attribute, $value, $fail) use ($request) {
                    if (in_array($request->primary_role_id, [5, 6]) && ! preg_match('/^[A-Za-z0-9]{1,2}$/', $value)) {
                        $fail('Code must be exactly 2 digits for User type: RSM or DSM.');
                    }
                },
            ], // for SM, RSM, DSM

            'tbl_pso_user_type_id' => 'required_if:primary_role_id,6', // for DSM
            'parent_rsm_code' => 'required_if:primary_role_id,6', // for DSM

            'rsm_region_id' => 'required_if:primary_role_id,5', // for RSM
            'parent_sm_code' => 'required_if:primary_role_id,5', // for RSM
            'buddy_name' => 'nullable|string',               // for RSM
            'buddy_phone' => 'nullable|string',               // for RSM

            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,,webp|max:2048',
        ], [
            'primary_role_id.required' => 'The `User type` field is required.',
            'userid.regex' => 'The `User ID` field must be 7 digit.',
            'user_code.required_if' => 'The `User code` field is required for User type: SM, RSM, DSM.',
            'tbl_pso_user_type_id.required_if' => 'The `DSM Type` field is required for User type: DSM.',
            'parent_rsm_code.required_if' => 'The `Parent RSM code` field is required for User type: DSM.',
            'rsm_region_id.required_if' => 'The `RSM Region` field is required for User type: RSM.',
            'parent_sm_code.required_if' => 'The `Parent SM Code` field is required for User type: RSM.',
        ]);

        try {
            $validated['password'] = Hash::make(env('USER_DEFAULT_PASSWORD', 'momenta@123'));

            $role = Role::findOrFail($validated['primary_role_id']);

            if ($validated['user_code']) {
                $user_code_exists = User::query()
                    ->where('user_code', $validated['user_code'])
                    ->where('primary_role_id', $validated['primary_role_id'])
                    ->first();

                throw_if($user_code_exists, new \Exception("User code `{$validated['user_code']}` already exists"));
            }

            if ($role->slug == 'dsm') {
                $parent_rsm = User::query()
                    ->where('user_code', $validated['parent_rsm_code'])
                    ->where('primary_role_id', 5) // 5 - rsm
                    ->first();
                throw_unless($parent_rsm, new \Exception('Parent RSM code not found'));

                $validated['supervisor_user_code'] = $validated['parent_rsm_code'];
            }

            if ($role->slug == 'rsm') {
                $parent_sm = User::query()
                    ->where('user_code', $validated['parent_sm_code'])
                    ->where('primary_role_id', 4) // 4 - sm
                    ->first();
                throw_unless($parent_sm, new \Exception('Parent SM code not found'));

                $validated['supervisor_user_code'] = $validated['parent_sm_code'];
            }

            if ($request->hasFile('photo')) {
                $photo_path = $this->fileUploadService->upload('photo', 'users/photo');
                $validated['photo'] = $photo_path;
            }

            DB::transaction(function () use ($validated, $role) {
                if ($role->slug == 'rsm' && $validated['buddy_name']) {
                    $buddy_info = [
                        'rsm_user_id' => null,
                        'buddy_name' => $validated['buddy_name'],
                        'buddy_phone' => $validated['buddy_phone'],
                        'tbl_business_business_code' => $validated['tbl_business_business_code'],
                    ];
                }

                unset($validated['parent_rsm_code'], $validated['parent_sm_code'], $validated['buddy_name'], $validated['buddy_phone']);

                $user = User::create($validated);

                DB::table('role_user')->insert([
                    'role_id' => $validated['primary_role_id'],
                    'user_id' => $user->id,
                    'is_primary' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($role->slug == 'rsm') {
                    $buddy_info['rsm_user_id'] = $user->id;
                    BuddyInfo::create($buddy_info);
                }
            });

            flash()->success('Successfully created');

            return redirect()->route('users.index');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back()->withInput($request->all());
    }

    public function show(User $user)
    {
        $user->load('user_type');

        return view('users.profile', compact('user'));
    }

    public function edit(User $user)
    {
        $user->load('buddy_info');

        $roles = Role::active()
            ->where('slug', '!=', 'pso')
            ->get();
        $business_units = Business::active()->get();
        $depots = Depot::active()->get();
        $pso_user_types = PsoUserType::active()->get();
        $regions = Region::active()->get();

        return view('users.edit', compact('user', 'roles', 'business_units', 'regions', 'depots', 'pso_user_types'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|max:150',
            'designation' => 'required|max:200',
            // 'userid' => 'required|max:20|unique:'.User::class,
            'phone' => ['nullable', 'regex:/^01[0-9]{9}$/'],
            'tbl_business_business_code' => 'required|max:2',
            'primary_role_id' => 'required|integer',
            'tbl_depot_id' => 'nullable',

            'user_code' => [
                'required_if:primary_role_id,4,5,6',
                function ($attribute, $value, $fail) use ($request) {
                    if (in_array($request->primary_role_id, [5, 6]) && ! preg_match('/^[A-Za-z0-9]{1,2}$/', $value)) {
                        $fail('Code must be exactly 2 digits for User type: RSM or DSM.');
                    }
                },
            ], // for SM, RSM, DSM

            'tbl_pso_user_type_id' => 'required_if:primary_role_id,6', // for DSM
            'parent_rsm_code' => 'required_if:primary_role_id,6', // for DSM

            'rsm_region_id' => 'required_if:primary_role_id,5', // for RSM
            'parent_sm_code' => 'required_if:primary_role_id,5', // for RSM
            'buddy_name' => 'nullable|string', // for RSM
            'buddy_phone' => 'nullable|string', // for RSM

            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,,webp|max:2048',
        ], [
            'primary_role_id.required' => 'The `User type` field is required.',
            'user_code.required_if' => 'The `User code` field is required for User type: SM, RSM, DSM.',
            'tbl_pso_user_type_id.required_if' => 'The `DSM Type` field is required for User type: DSM.',
            'parent_rsm_code.required_if' => 'The `Parent RSM code` field is required for User type: DSM.',
            'rsm_region_id.required_if' => 'The `RSM Region` field is required for User type: RSM.',
            'parent_sm_code.required_if' => 'The `Parent SM Code` field is required for User type: RSM.',
        ]);

        try {
            $role = Role::findOrFail($validated['primary_role_id']);

            if ($validated['user_code']) {
                $user_code_exists = User::query()
                    ->where('user_code', $validated['user_code'])
                    ->where('primary_role_id', $validated['primary_role_id'])
                    ->where('id', '!=', $user->id)
                    ->first();

                throw_if(
                    $user_code_exists,
                    new \Exception("User code `{$validated['user_code']}` already exists")
                );
            }

            if ($role->slug == 'dsm') {
                $parent_rsm = User::query()
                    ->where('user_code', $validated['parent_rsm_code'])
                    ->where('primary_role_id', 5) // 5 - rsm
                    ->first();
                throw_unless($parent_rsm, new \Exception('Parent RSM code not found'));

                $validated['supervisor_user_code'] = $validated['parent_rsm_code'];
            }

            if ($role->slug == 'rsm') {
                $parent_sm = User::query()
                    ->where('user_code', $validated['parent_sm_code'])
                    ->where('primary_role_id', 4) // 4 - sm
                    ->first();
                throw_unless($parent_sm, new \Exception('Parent SM code not found'));

                $validated['supervisor_user_code'] = $validated['parent_sm_code'];
            }

            if ($request->hasFile('photo')) {
                $photo_path = $this->fileUploadService->upload('photo', 'users/photo');
                $validated['photo'] = $photo_path;
            }

            $validated['updated_by'] = auth()->id();

            if (in_array($role->slug, ['admin', 'marketing', '', 'support', 'trainer'])) {
                $validated['user_code'] = $user->user_code ? $user->user_code . '_old' : null;
                $validated['supervisor_user_code'] = $user->supervisor_user_code ? $user->supervisor_user_code . '_old' : null;
                $validated['rsm_region_id'] = null;
                $validated['rsm_region'] = null;
                $validated['tbl_pso_user_type_id'] = null;
            }

            if ($role->slug == 'sm') {
                $validated['supervisor_user_code'] = null;
                $validated['rsm_region_id'] = null;
                $validated['rsm_region'] = null;
                $validated['tbl_pso_user_type_id'] = null;
            }

            DB::transaction(function () use ($validated, $role, $user) {
                $buddy_info = [
                    'rsm_user_id' => $user->id,
                    'buddy_name' => $validated['buddy_name'],
                    'buddy_phone' => $validated['buddy_phone'],
                    'tbl_business_business_code' => $validated['tbl_business_business_code'],
                ];

                unset($validated['parent_rsm_code'], $validated['parent_sm_code'], $validated['buddy_name'], $validated['buddy_phone']);

                $previous_data = $user;
                $user->fill($validated);

                if ($user->isDirty()) {
                    $changed_data = $user->getDirty();
                    $last_update_state = [
                        'changed_data' => $changed_data,
                        'previous_data' => $previous_data,
                    ];
                    $user->last_update_state = $last_update_state;

                    UserUpdateTracker::create([
                        'user_id' => $user->id,
                        'updated_by' => auth()->id(),
                        'previous_data' => $previous_data,
                        'changed_data' => $changed_data,
                    ]);
                }
                // $user->update($validated);
                $user->save();

                DB::table('role_user')->where('user_id', $user->id)->delete();
                DB::table('role_user')->insert([
                    'role_id' => $validated['primary_role_id'],
                    'user_id' => $user->id,
                    'is_primary' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($role->slug == 'rsm') {
                    $buddy_info['rsm_user_id'] = $user->id;
                    $user->buddy_info()->delete();
                    BuddyInfo::create($buddy_info);
                }
            });

            flash()->success('Successfully updated');

            // return redirect()->route('users.index');
            return back();
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back()->withInput($request->all());
    }

    public function destroy(User $user)
    {
        try {
            $user->userid = $user->userid . '_deleted_' . time();

            $user->user_code = $user->user_code ? $user->user_code . '_deleted_' . time() : null;
            $user->save();

            $user->delete();
            flash()->success('User deleted successfully');
        } catch (\Exception $e) {
            // flash()->error($e->getMessage());
            flash()->error('User delete failed.');
        }

        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->is_active = $request->toggle_input == 'true' ? 1 : 0;
            $user->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function passwordReset(User $user)
    {
        try {
            $user->password = Hash::make(env('USER_DEFAULT_PASSWORD'));
            $user->save();
            flash()->success('Password reset successfully');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function resetPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $user = User::findOrFail($id);
        if (! Hash::check($validated['current_password'], $user->password)) {
            flash()->error('Current password does not match our records');

            return back();
        }

        try {
            $user->password = Hash::make($validated['password']);
            $user->save();

            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/');
        } catch (\Exception $e) {
            flash()->error('Password change failed. Try again later');

            return back();
        }
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'user_bulk_excel' => 'required|mimes:xlsx',
        ]);

        $user = auth()->user();

        try {
            $importedExcel = Excel::toArray(new UsersImport, $request->file('user_bulk_excel'));

            throw_unless($importedExcel, new \Exception('Invalid excel data'));

            $preparedData = [];
            $failedData = [];

            $excelData = $importedExcel[0];

            foreach ($excelData as $row) {
                if ($row[0] && $row[1] && $row[7]) {
                    $department_name = trim($row[7]);

                    $checkUserExists = DB::table('users')->where('userid', trim($row[1]))->first();
                    $department = Department::query()->whereLike('name', "%{$department_name}%")->first();

                    if (! $checkUserExists) {
                        $user = [
                            'name' => trim($row[0]),
                            'userid' => trim($row[1]),
                            'gender' => strtolower($row[2]) == 'male' ? 1 : (strtolower($row[2]) == 'female' ? 2 : 3),
                            'designation' => trim($row[3]),
                            'phone' => $row[4],
                            'email' => trim($row[5]),
                            'address' => $row[6],
                            'department_id' => $department ? $department->id : null,
                            'password' => Hash::make(env('USER_DEFAULT_PASSWORD', 'Prime@2025')),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $preparedData[] = $user;
                    }
                } else {
                    $failedData[] = $row;
                }
            }

            if ($failedData) {
                session()->flash('error_data', $failedData);
            }

            throw_if($failedData, new \Exception('No data saved. There are some invalid data in the excel. Please fix & upload again', 105));

            DB::transaction(function () use ($preparedData) {

                $userIds = array_column($preparedData, 'userid');

                DB::table('users')->insert($preparedData);

                $inserted_users = DB::table('users')->whereIn('userid', $userIds)->get();

                $user_type = Role::where('slug', 'employee')->first();

                $user_roles = [];
                foreach ($inserted_users as $user) {
                    $user_roles[] = [
                        'role_id' => $user_type->id, // FO
                        'user_id' => $user->id,
                        'is_primary' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $user_roles = $this->duplicate_record_remove($user_roles);

                if ($user_roles) {
                    DB::table('role_user')->insert($user_roles);
                }
            });
            session()->flash('success', 'User data inserted successfully');
        } catch (\Exception $e) {
            if ($e->getCode() == 23000) {
                session()->flash('error', 'Failed! No data saved. Duplicate USER CODE found');
            } else {
                session()->flash('error', $e->getMessage());
            }
        }

        return back();
    }

    // This method sync user primary role in the `role_user` table
    public function sync_user_roles()
    {
        if (env('APP_ENV') != 'local') {
            return 'This route is for data migration purpose only. this will work only in local environment';
        }

        // read all users by chunk, check if user primary role exists on `role_user` table and insert if not exists
        User::query()
            ->with('roles')
            ->whereNotNull('primary_role_id')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    // $user_roles = $user->roles->pluck('id')->toArray();

                    // $user_primary_role = $user->roles->where('is_primary', 1)->first();

                    $role_exists = $user->roles->where('id', $user->primary_role_id)->first();

                    if (! $role_exists) {
                        DB::table('role_user')->insert([
                            'role_id' => $user->primary_role_id,
                            'user_id' => $user->id,
                            'is_primary' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        return 'User role sync completed';
    }

    public function business_wise_users($business_code, $role_slug)
    {
        $role = Role::query()
            ->where('slug', $role_slug)
            ->first();

        $users = $this->userService->business_wise_users($business_code, $role->id);
        if (request()->ajax()) {
            return response()->json($users);
        }

        return $users;
    }

    public function role_wise_users($role_slug, $supervisor_user_code = null)
    {
        $users = $this->userService->role_wise_users($role_slug, $supervisor_user_code);
        if (request()->ajax()) {
            return response()->json($users);
        }

        return $users;
    }

    public function get_product_group_wise_user(Request $request)
    {
        if ($request->ajax()) {

            $rsm_role = Role::where('slug', 'rsm')->first();
            $dsm_role = Role::where('slug', 'dsm')->first();
            $pso_role = Role::where('slug', 'pso')->first();

            $rsm_users = [];
            $dsm_users = [];
            $pso_users = [];

            if ($request->has('business_code') && $request->business_code) {
                $rsm_users = $this->userService->business_wise_users($request->business_code, $rsm_role->id);

                if (auth()->user()->primary_role_id == Role::where('slug', 'sm')->value('id')) {
                    $auth_user_code = auth()->user()->user_code;
                    $rsm_users = $rsm_users->filter(function ($user) use ($auth_user_code) {
                        return $user->supervisor_user_code === $auth_user_code;
                    })->values();
                }
                $rsm_users->load('region');

                $rsm_users = $rsm_users->map(function ($user) {
                    return [
                        'user_code' => $user->user_code,
                        'rsm_region' => $user->region->name ?? '-',
                    ];
                });
            }

            if ($request->has('rsm_code') && $request->rsm_code) {
                $dsm_users = $this->userService->role_wise_users($dsm_role->slug, $request->rsm_code);
            }
            if ($request->has('dsm_code') && $request->dsm_code) {
                $pso_users = $this->userService->role_wise_users($pso_role->slug, $request->dsm_code);
            }

            $query = ProductGroup::where('status', 1);
            if ($request->has('business_code') && $request->business_code) {
                $query->where('tbl_business_business_code', $request->business_code);
            }

            $product_groups = $query->get();

            return response()->json([
                'product_groups' => $product_groups,
                'rsm_users' => $rsm_users,
                'dsm_users' => $dsm_users,
                'pso_users' => $pso_users,
            ]);
        }

    }

    public function export(Request $request)
    {
        $pso_role = Role::where('slug', 'pso')->first();

        $users = User::query()
            ->with([
                'business',
                'user_type:id,title,slug',
                'rsm:id,name,userid,user_code,supervisor_user_code,rsm_region', // RSM
                'sm:id,name,userid,user_code,supervisor_user_code',             // SM
                'region:id,name',
            ])
            ->when(request()->role_id, function ($q) {
                $q->where('primary_role_id', request()->role_id);
            })
            ->where('primary_role_id', '!=', $pso_role->id)
            ->staff()
            ->orderBy('id', 'desc')
            ->get();

        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'name' => $user->name ?? '-',
                'reanata_id' => $user->userid ?? '-',
                'user_code' => $user->user_code ?? '-',
                'business_code' => $user->tbl_business_business_code ?? '-',
                'designation' => $user->designation ?? '-',
                'supervisor' => $user->user_type?->slug == 'dsm'
                    ? ($user->rsm ? "RSM code: {$user->rsm->user_code}" : '-')
                    : ($user->user_type?->slug == 'rsm'
                        ? ($user->sm ? "SM code: {$user->sm->user_code}" : '-')
                        : '-'),
                'phone' => $user->phone ?? '-',
                'role' => $user->user_type?->title ?? '-',
                'rsm_region' => $user->region?->name ?? '-',
                'status' => $user->is_active ? 'Active' : 'Inactive',
            ];

        }

        $file_name = 'User_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new UserExport($data), $file_name);
    }
}
