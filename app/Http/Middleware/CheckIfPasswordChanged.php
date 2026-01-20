<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfPasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $exceptRoutes = [
            'login',
            'logout',
            'password-change',
        ];

        if ($request->is($exceptRoutes)) {
            return $next($request);
        }

        if (Auth::check() && Auth::user()->is_password_changed != 1) {
            // if (Auth::check() && Auth::user()->is_password_changed != 1 && config('app.env') === 'production') {
            return redirect()->route('password-change')->withErrors([
                'change_required' => 'You must change your password before continuing.',
            ]);
        }

        return $next($request);
    }
}
