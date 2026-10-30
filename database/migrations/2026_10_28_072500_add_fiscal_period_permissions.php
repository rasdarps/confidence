<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = config('permission.defaults.guard_name', config('auth.defaults.guard', 'web'));

        $permissions = [
            'accounting-fiscal-period-create',
            'accounting-fiscal-period-edit',
            'accounting-fiscal-period-reopen',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => $guardName,
            ]);

            // Give to roles that already have accounting-fiscal-period-close
            $roles = Role::query()->whereHas('permissions', function ($q) {
                $q->where('name', 'accounting-fiscal-period-close');
            })->get();

            foreach ($roles as $role) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', [
            'accounting-fiscal-period-create',
            'accounting-fiscal-period-edit',
            'accounting-fiscal-period-reopen',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

