<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        $this->set_session_info($user);

        $user->last_login = now();
        $user->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Cache::forget('user_permissions_' . auth()->user()->id);
        Cache::forget('user_menus_' . auth()->user()->id);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function fakeLogin(Request $request): RedirectResponse
    {
        if (app()->environment('local')) {
            $user = User::query()
                ->where('userid', $request->userid)
                ->where('is_active', 1)
                ->firstOrFail();

            $this->set_session_info($user);

            $user->last_login = now();
            $user->save();

            Auth::login($user);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        abort(404);
    }

    private function set_session_info(User $user)
    {
        /* $pso_type = Role::query()
            ->where('slug', 'pso')
            ->first();

        if ($user->primary_role_id == $pso_type->id) {
            Auth::guard('web')->logout();
            return redirect()->route('login')
                ->with('error', 'You are not allowed to login to the system.');
        } */

        $roles = $user->roles;
        if ($roles->count()) {
            // $primary_role = $user->roles()->where('is_primary', 1)->first();
            $primary_role = $user->user_type;
            session(['roles' => $roles]);
            session(['role' => $primary_role]);
            session(['role_id' => $primary_role->id]);
        }
    }
}
