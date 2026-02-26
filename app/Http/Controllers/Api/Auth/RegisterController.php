<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\TempUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|unique:users,phone_number|unique:temp_users,phone_number',
            'password' => 'required|min:6',
        ]);

        $tempUser = TempUser::create([
            'name' => $request->name,
            'userid' => 'USR-' . strtoupper(Str::random(8)),
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'primary_role_id' => null,
            'expires_at' => now()->addMinutes(10),
        ]);

        $code = app()->isProduction() ? rand(1200, 7879) : 1111;

        Otp::create([
            'user_id' => $tempUser->id,
            'phone_number' => $tempUser->phone_number,
            'otp' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'OTP sent',
            'temp_user_id' => $tempUser->id,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'temp_user_id' => 'required|exists:temp_users,id',
            'otp' => 'required|digits:4',
        ]);

        $otp = Otp::where('user_id', $request->temp_user_id)
            ->where('otp', $request->otp)
            ->where('is_used', false)
            ->where('expires_at', '>=', now())
            ->first();

        if (! $otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP',
            ], 400);
        }

        $otp->update(['is_used' => true]);

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully. Please select user type to complete registration.',
            'temp_user_id' => (int) $request->temp_user_id,
        ]);
    }

    public function selectUserType(Request $request)
    {
        $request->validate([
            'temp_user_id' => 'required|exists:temp_users,id',
            'role' => ['required', Rule::in(['tenant', 'landowner'])],
        ]);

        $roleMap = [
            'landowner' => 1,
            'tenant' => 2,
        ];

        $tempUser = TempUser::query()->findOrFail($request->temp_user_id);

        if ($tempUser->expires_at && $tempUser->expires_at->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Registration session expired. Please register again.',
            ], 400);
        }

        $verifiedOtp = Otp::where('user_id', $tempUser->id)
            ->where('is_used', true)
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $verifiedOtp) {
            return response()->json([
                'status' => false,
                'message' => 'OTP is not verified yet.',
            ], 400);
        }

        DB::transaction(function () use ($request, $tempUser, $roleMap) {
            $user = User::create([
                'name' => $tempUser->name,
                'userid' => $tempUser->userid,
                'phone_number' => $tempUser->phone_number,
                'password' => $tempUser->password,
                'primary_role_id' => $roleMap[$request->role],
                'is_active' => true,
                'is_password_changed' => true,
            ]);

            DB::table('role_user')->insert([
                'role_id' => $roleMap[$request->role],
                'user_id' => $user->id,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tempUser->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Registration completed successfully',
        ]);
    }
}
