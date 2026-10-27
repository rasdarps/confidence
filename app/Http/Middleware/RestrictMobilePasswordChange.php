<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class RestrictMobilePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('api.auth.login')) {
            return $next($request);
        }

        $user = Auth::guard('sanctum')->user();
        if (!$user instanceof User) {
            return $next($request);
        }
        $token = $user->currentAccessToken();
        $restricted = $token instanceof PersonalAccessToken && $token->abilities === ['password:change'];
        if ($user && ($user->must_change_password || $restricted) && !$request->routeIs('api.auth.change-password')) {
            return response()->json([
                'message' => 'Complete the first-login password change before accessing this endpoint.',
                'code' => 'password_change_required',
            ], 403);
        }

        return $next($request);
    }
}