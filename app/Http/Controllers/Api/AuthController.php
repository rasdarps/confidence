<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MobileUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where(fn ($query) => $query->where('email', $credentials['username'])
                ->orWhere('username', $credentials['username']))
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->must_change_password) {
            $expiresAt = now()->addMinutes(10);
            $token = $user->createToken('flutter-password-change', ['password:change'], $expiresAt);
            return response()->json([
                'message' => 'Change your temporary password before accessing the application.',
                'code' => 'password_change_required',
                'requires_password_change' => true,
                'token_type' => 'Bearer',
                'access_token' => $token->plainTextToken,
                'expires_at' => $expiresAt->toISOString(),
            ]);
        }

        $token = $user->createToken('flutter-mobile');

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'requires_password_change' => false,
            'user' => (new MobileUserResource($user))->resolve($request),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken && $token->abilities === ['password:change'], 403, 'A password-change token is required.');

        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $result = DB::transaction(function () use ($request, $token, $data) {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $activeToken = $user->tokens()->whereKey($token->id)->lockForUpdate()->first();
            abort_unless($activeToken && (!$activeToken->expires_at || $activeToken->expires_at->isFuture()), 401, 'Password-change token expired or revoked.');
            abort_unless($user->must_change_password && $activeToken->abilities === ['password:change'], 403, 'First-login password change is not required.');

            if (!Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
            }
            if (Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages(['password' => 'Choose a password different from your temporary password.']);
            }

            $user->password = Hash::make($data['password']);
            $user->must_change_password = false;
            $user->save();
            $user->tokens()->delete();
            $normalToken = $user->createToken('flutter-mobile');

            return [
                'token_type' => 'Bearer',
                'access_token' => $normalToken->plainTextToken,
                'requires_password_change' => false,
                'user' => (new MobileUserResource($user))->resolve($request),
            ];
        });

        return response()->json($result);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken, 400, 'A personal access token is required.');

        $token->delete();

        return response()->noContent();
    }
}