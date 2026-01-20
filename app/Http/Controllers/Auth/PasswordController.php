<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.change-password');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'min:6'],
        ]);

        try {
            $user = Auth::user() ?? $request->user();

            if (! Hash::check($validated['current_password'], $user->password)) {
                flash()->error('Current password does not match our records');

                return back();
            }

            $user->update([
                'password' => Hash::make($validated['password']),
                'is_password_changed' => 1,
            ]);

            flash()->success('Password Changed Successfully');

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return back();
        }
    }
}
