<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create([
            'name' => 'Mobile Operator',
            'username' => 'mobile-operator',
            'email' => 'mobile@example.test',
            'phone' => '0241111222',
            'password' => Hash::make('Correct-Horse-2026!'),
            'user_type' => 'Staff',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'sale-create', 'guard_name' => 'web']));
        return $user;
    }

    public function test_login_by_username_or_email_issues_token_and_me_returns_only_safe_profile(): void
    {
        $this->user();

        foreach (['mobile-operator', 'mobile@example.test'] as $login) {
            $response = $this->postJson('/api/auth/login', ['username' => $login, 'password' => 'Correct-Horse-2026!'])
                ->assertOk()->assertJsonPath('token_type', 'Bearer')
                ->assertJsonPath('user.name', 'Mobile Operator')
                ->assertJsonPath('user.permissions.0', 'sale-create');
            $token = $response->json('access_token');
            $this->assertNotEmpty($token);

            $this->withToken($token)->getJson('/api/user')->assertOk()
                ->assertJsonPath('data.username', 'mobile-operator')
                ->assertJsonMissingPath('data.password')
                ->assertJsonMissingPath('data.remember_token')
                ->assertJsonMissingPath('data.created_by');

            $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();
            Auth::forgetGuards();
            $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
            $this->flushHeaders();
        }
    }

    public function test_invalid_credentials_do_not_issue_tokens(): void
    {
        $user = $this->user();
        $this->postJson('/api/auth/login', ['username' => $user->username, 'password' => 'wrong-password'])
            ->assertUnauthorized()->assertJsonPath('message', 'Invalid credentials.');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_requires_both_fields(): void
    {
        $this->postJson('/api/auth/login', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_first_login_issues_restricted_token_then_changes_password_and_issues_normal_token(): void
    {
        $user = $this->user();
        $user->update(['must_change_password' => true]);
        $oldToken = $user->createToken('existing-device')->plainTextToken;
        $login = $this->postJson('/api/auth/login', ['username' => $user->username, 'password' => 'Correct-Horse-2026!'])
            ->assertOk()->assertJsonPath('requires_password_change', true)
            ->assertJsonPath('code', 'password_change_required')->assertJsonMissingPath('user');
        $restricted = $login->json('access_token');
        $record = PersonalAccessToken::findToken($restricted);
        $this->assertSame(['password:change'], $record->abilities);
        $this->assertNotNull($record->expires_at);
        $this->assertEqualsWithDelta(600, now()->diffInSeconds($record->expires_at), 2);
        $this->tokenRequest($restricted, 'get', '/api/user')->assertForbidden()->assertJsonPath('code', 'password_change_required');
        $this->tokenRequest($oldToken, 'get', '/api/user')->assertForbidden();
        $this->tokenRequest($restricted, 'post', '/api/auth/logout')->assertForbidden();

        Route::middleware(['api', 'auth:sanctum'])->get('/api/test-business-route', fn () => response()->json(['business' => true]));
        $this->tokenRequest($restricted, 'get', '/api/test-business-route')->assertForbidden();
        $changed = $this->tokenRequest($restricted, 'post', '/api/auth/change-password', [
            'current_password' => 'Correct-Horse-2026!', 'password' => 'New-Secure-2026!',
            'password_confirmation' => 'New-Secure-2026!',
        ])->assertOk()->assertJsonPath('requires_password_change', false)->assertJsonPath('user.must_change_password', false);
        $normal = $changed->json('access_token');
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('New-Secure-2026!', $user->fresh()->password));
        $this->assertNull(PersonalAccessToken::findToken($restricted));
        $this->assertNull(PersonalAccessToken::findToken($oldToken));
        $this->tokenRequest($restricted, 'get', '/api/user')->assertUnauthorized();
        $this->tokenRequest($normal, 'get', '/api/user')->assertOk();
        $this->tokenRequest($normal, 'get', '/api/test-business-route')->assertOk();
        $this->tokenRequest($normal, 'post', '/api/auth/change-password')->assertForbidden();
        $this->tokenRequest($normal, 'post', '/api/auth/logout')->assertNoContent();
    }

    public function test_password_change_validation_and_wrong_password_leave_account_and_token_unchanged(): void
    {
        $user = $this->user();
        $user->update(['must_change_password' => true]);
        $token = $user->createToken('change', ['password:change'], now()->addMinutes(10))->plainTextToken;
        $this->tokenRequest($token, 'post', '/api/auth/change-password', [
            'current_password' => 'wrong', 'password' => 'New-Secure-2026!', 'password_confirmation' => 'New-Secure-2026!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->tokenRequest($token, 'post', '/api/auth/change-password', [
            'current_password' => 'Correct-Horse-2026!', 'password' => 'short', 'password_confirmation' => 'mismatch',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->tokenRequest($token, 'post', '/api/auth/change-password', [
            'current_password' => 'Correct-Horse-2026!', 'password' => 'Correct-Horse-2026!', 'password_confirmation' => 'Correct-Horse-2026!',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('Correct-Horse-2026!', $user->fresh()->password));
        $this->assertNotNull(PersonalAccessToken::findToken($token));
    }

    public function test_expired_and_stale_restricted_tokens_cannot_access_normal_api_or_change_password(): void
    {
        $user = $this->user();
        $user->update(['must_change_password' => true]);
        $expired = $user->createToken('expired', ['password:change'], now()->subMinute())->plainTextToken;
        $this->tokenRequest($expired, 'post', '/api/auth/change-password')->assertUnauthorized();
        $valid = $user->createToken('stale', ['password:change'], now()->addMinutes(10))->plainTextToken;
        $user->update(['must_change_password' => false]);
        $this->tokenRequest($valid, 'get', '/api/user')->assertForbidden();
        $this->tokenRequest($valid, 'post', '/api/auth/change-password', [
            'current_password' => 'Correct-Horse-2026!', 'password' => 'New-Secure-2026!', 'password_confirmation' => 'New-Secure-2026!',
        ])->assertForbidden();
    }

    private function tokenRequest(string $token, string $method, string $path, array $data = [])
    {
        Auth::forgetGuards();
        return $this->withToken($token)->{$method.'Json'}($path, $data);
    }

    public function test_login_and_password_change_attempts_are_throttled(): void
    {
        $user = $this->user();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', ['username' => $user->username, 'password' => 'incorrect'])->assertUnauthorized();
        }
        $this->postJson('/api/auth/login', ['username' => $user->username, 'password' => 'incorrect'])->assertStatus(429);

        $user->update(['must_change_password' => true]);
        $token = $user->createToken('change', ['password:change'], now()->addMinutes(10))->plainTextToken;
        $data = ['current_password' => 'incorrect', 'password' => 'New-Secure-2026!', 'password_confirmation' => 'New-Secure-2026!'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->tokenRequest($token, 'post', '/api/auth/change-password', $data)->assertUnprocessable();
        }
        $this->tokenRequest($token, 'post', '/api/auth/change-password', $data)->assertStatus(429);
        $this->assertTrue($user->fresh()->must_change_password);
    }
}