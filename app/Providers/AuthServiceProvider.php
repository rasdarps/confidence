<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function (User $user) {
            $hasSuperAdminRole = $user->roles->contains(function ($role) {
                return strtolower((string) $role->name) === 'super admin';
            });

            return strtolower(trim((string) ($user->user_type ?? ''))) === 'super admin'
                || $hasSuperAdminRole
                ? true
                : null;
        });
    }
}
