<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChangeOnFirstLogin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('pos.require_password_change_on_first_login', false)) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        if ($request->routeIs('change.password', 'update.password', 'admin.logout', 'logout')) {
            return $next($request);
        }

        $user = Auth::user();
        if (! $user || ! (bool) ($user->must_change_password ?? false)) {
            return $next($request);
        }

        // Exclude Super Admin users from password change enforcement
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        return redirect()->route('change.password')->with([
            'message' => 'You must change your password before continuing.',
            'alert-type' => 'warning',
        ]);
    }
}
