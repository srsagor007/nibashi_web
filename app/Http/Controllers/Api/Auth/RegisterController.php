<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
use App\Models\TempUser;
use Carbon\Carbon;


class RegisterController extends Controller
{
  
    public function register(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|unique:users,phone_number',
            'password'     => 'required|min:6',
            'role'         => ['required', Rule::in(['tenant', 'landowner'])],
        ]);

        $roleMap = [
            'landowner' => 1,
            'tenant'    => 2,
        ];

        $tempUser = TempUser::create([
            'name'            => $request->name,
            'userid'          => 'USR-' . strtoupper(Str::random(8)),
            'phone_number'    => $request->phone_number,
            'password'        => Hash::make($request->password),
            'primary_role_id' => $roleMap[$request->role],
            'expires_at'      => now()->addMinutes(10),
        ]);

        $code = app()->isProduction() ? rand(1200, 7879) : 1111;

        Otp::create([
            'user_id'      => $tempUser->id,
            'phone_number' => $tempUser->phone_number,
            'otp'          => $code,
            'expires_at'   => now()->addMinutes(5),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'OTP sent',
            'temp_user_id' => $tempUser->id,
        ]);
    }

   public function verifyOtp(Request $request)
    {
        $request->validate([
            'temp_user_id' => 'required|exists:temp_users,id',
            'otp'          => 'required|digits:4',
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

        DB::transaction(function () use ($otp, $request) {

            $otp->update(['is_used' => true]);

            $tempUser = TempUser::findOrFail($request->temp_user_id);

            $user = User::create([
                'name'            => $tempUser->name,
                'userid'          => $tempUser->userid,
                'phone_number'    => $tempUser->phone_number,
                'password'        => $tempUser->password,
                'primary_role_id' => $tempUser->primary_role_id,
                'is_active'       => true,
                'is_password_changed' => true,
            ]);

            DB::table('role_user')->insert([
                'role_id' => $tempUser->primary_role_id,
                'user_id' => $user->id,
                'is_primary' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 🧹 cleanup
            $tempUser->delete();
        });

        return response()->json([
            'status'  => true,
            'message' => 'Registration completed successfully',
        ]);
    }


}
