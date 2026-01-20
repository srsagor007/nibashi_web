<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppMenu;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\SmsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected FileUploadService $fileUploadService, protected SmsService $smsService) {}

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'string|required|max:10',
            'password' => 'required|string',
            'device_data' => 'string|required',
            'device_token' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        try {
            $user = User::query()
                ->where('userid', $request->userid)
                ->first();
            throw_unless($user, new Exception('Invalid User ID or Password.'), 401);



            $password_matched = Hash::check($request->password, $user->password);
            if (config('app.env') !== 'production' && $request->password == '111111') {
                $password_matched = true;
            }

            throw_unless($password_matched, new Exception('Invalid User ID or Password'), 401);
            throw_unless($user->is_active == 1, new Exception('User deactivated. Contact system admin'), 401);

            if (env('APP_ENV') == 'production') {
                $user->tokens()->delete();
            }

            $token = $user->createToken($request->device_data ?? 'API token');

            $user->last_login = Carbon::now()->toDateTimeString();
            $user->device_token = $request->device_token ?? $user->device_token;
            $user->save();

            $user->load([
                'user_type:id,title,slug'
            ]);

            $data = [
                'user' => $user,
                'token' => $token->plainTextToken,
                // 'app_menu_access' => $app_menu_access,
            ];

            return response()->success($data, 'Successfully logged in', 200);
        } catch (Exception $e) {
            return response()->error($e->getMessage(), null, 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->success(null, 'Successfully logged out', 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $rsm_code = null;

        $user->load([
            'user_type:id,title,slug',
            'business:business_code,business_name',
            'depot:id,depot_code,depot_name',
            'region:id,name',
            'dsm' => function ($q) {
                $q
                    ->with([
                        'rsm:id,name,userid,user_code,rsm_region_id',
                        'rsm.region:id,name',
                    ])
                    ->select('id', 'name', 'userid', 'user_code', 'designation', 'tbl_business_business_code', 'supervisor_user_code', 'tbl_pso_user_type_id');
            },
            'rsm:id,name,userid,user_code,designation,tbl_business_business_code,supervisor_user_code,rsm_region_id',
            'sm:id,name,userid,user_code,designation,tbl_business_business_code,supervisor_user_code',
            'pso_dsm_user_type:id,pso_user_type_name',
            'buddy_info:id,rsm_user_id,buddy_name,buddy_phone',
        ]);

        if ($user->user_type->slug == 'pso') {
            unset($user->region);
            $user->region = $user->dsm->rsm->region;
            $rsm_code = $user->dsm->rsm->user_code;
            $user->gender_text = $user->gender_text;
        }

        if ($user->user_type->slug == 'dsm') {
            unset($user->dsm);
            unset($user->region);
            $user->dsm = null;
            $user->region = $user->rsm->region;
            $rsm_code = $user->rsm->user_code;
            $user->gender_text = $user->gender_text;
        }

        if ($user->user_type->slug == 'rsm') {
            $rsm_code = $user->user_code;
            $user->gender_text = $user->gender_text;
        }

        $today = Carbon::today();

        $birthday_message = '';
        $anniversary_message = '';

        if ($user->dob && Carbon::parse($user->dob)->isSameDay($today)) {
            $birthday_message = 'Happy Birthday to You, Wish You All the Best From Reneta';
        } elseif ($user->date_of_marriage && Carbon::parse($user->date_of_marriage)->isSameDay($today)) {
            $anniversary_message = 'Happy Marriage Anniversary, Wish You All the Best From Reneta';
        }

        $app_menu_access = AppMenu::query()
            ->when($rsm_code != null, function ($q) use ($rsm_code) {
                $q->leftJoin('tbl_app_menu_access', 'tbl_app_menu.id', 'tbl_app_menu_access.tbl_app_menu_id')
                    ->where('tbl_app_menu_access.rsm_code', $rsm_code);
            })
            ->groupBy('tbl_app_menu.id')
            ->select('tbl_app_menu.id', 'tbl_app_menu.menu_name')
            ->get();

        return response()->success([
            'user' => $user,
            'app_menu_access' => $app_menu_access,
            'birthday_message' => $birthday_message,
            'anniversary_message' => $anniversary_message,
        ], 'User data found', 200);
    }

    public function updateProfile(Request $request)
    {
        $id = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'gender' => 'nullable|integer|in:1,2,3',
            'dob' => 'nullable|date',
            'marital_status' => 'nullable|integer|in:1,2,3',
            'date_of_marriage' => 'nullable|date',
        ]);

        try {
            throw_if(
                $validator->fails(),
                new \Exception($validator->errors(), 422)
            );

            $data = [];
            $user = User::find($id);
            $data['gender'] = $request->gender ?? $user->gender;
            $data['dob'] = $request->dob ?? $user->dob;
            $data['marital_status'] = $request->marital_status ?? $user->marital_status;
            $data['date_of_marriage'] = $request->date_of_marriage ?? $user->date_of_marriage;

            $user->update($data);

            return response()->success(null, 'Profile updated successfully.', 200);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), null, 500);
        }
    }

    public function updateProfilePhoto(Request $request)
    {
        $id = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,,webp|max:2048',
        ]);

        throw_if(
            $validator->fails(),
            new \Exception($validator->errors(), 422)
        );

        try {
            $data = $validator->validate();

            if ($request->hasFile('photo')) {
                $photo_path = $this->fileUploadService->upload('photo', 'users/photo');
                $data['photo'] = $photo_path;
            }

            $user = User::find($id);

            $user->photo = $data['photo'];
            $user->save();

            // ->update($data);

            return response()->success($user, 'Profile photo updated successfully.', 200);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), null, 500);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6',
            'confirm_new_password' => 'required|string|min:6',
        ]);

        try {

            if ($request->new_password != $request->confirm_new_password) {
                return response()->error('New password and confirm password does not matched', null, 422);
            }

            $id = auth()->user()->id;

            $user = User::find($id);
            throw_unless($user, new Exception('Invalid User.'), 401);

            throw_if(
                ! Hash::check($request->old_password, $user->password),
                ValidationException::withMessages([
                    'old_password' => ['The old password is incorrect.'],
                ])
            );

            $user->update([
                'password' => Hash::make($request->new_password),
                'is_password_changed' => 1,
            ]);

            $user->tokens()->delete();

            return response()->success(null, 'Password changed successfully. Please log in again.', 200);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), null, 500);
        }
    }

    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rf_id' => 'required|string',
            'mobile_no' => [
                'required',
                'string',
                'regex:/^01[0-9]{9}$/',
            ],
        ]);

        try {
            $validated = $validator->validate();

            $user = User::query()
                ->where('userid', $request->rf_id)
                ->first();

            throw_unless($user, new Exception('Invalid User ID or Password.'), 404);

            $provided_phone = substr($request->mobile_no, 1);
            $user_phone = substr($user->phone, 1);

            if ($provided_phone !== $user_phone) {
                throw new Exception('Phone number does not match to your account.', 404);
            }

            $new_password = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $updated_password = Hash::make($new_password);

            $user->update([
                'password' => $updated_password,
                'is_password_changed' => 1,
            ]);

            $message = 'Your New Password is ' . $new_password;

            if (env('APP_ENV') !== 'local') {
                $this->smsService->sendSms($validated['mobile_no'], $message);
            }

            $user->tokens()->delete();

            return response()->success($user, 'New Password has been sent to your phone number.', 200);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), null, $th->getCode() ?: 500);
        }
    }
}
