<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $guardName = config('permission.defaults.guard_name', config('auth.defaults.guard', 'web'));
        $adminEmail = 'rasdarps@gmail.com';
        $adminUsername = 'rasdarps';
        $adminPhone = '0242222874';

        // Find or create the Super Admin role using the active guard.
        $role = Role::findOrCreate('Super Admin', $guardName);

        // Reuse an existing admin account when any unique identifier already exists.
        $user = User::query()
            ->where('email', $adminEmail)
            ->orWhere('username', $adminUsername)
            ->orWhere('phone', $adminPhone)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Kwaku Darpah',
                'email' => $adminEmail,
                'username' => $adminUsername,
                'phone' => $adminPhone,
                'password' => bcrypt('Juliana@1985'),
                'user_type' => 'Super Admin',
                'created_by' => null,
            ]);
        } else {
            $updates = [
                'name' => 'Kwaku Darpah',
                'phone' => $adminPhone,
                'user_type' => 'Super Admin',
                'created_by' => 1,
            ];

            if ($user->email !== $adminEmail && ! User::where('email', $adminEmail)->whereKeyNot($user->getKey())->exists()) {
                $updates['email'] = $adminEmail;
            }

            if ($user->username !== $adminUsername && ! User::where('username', $adminUsername)->whereKeyNot($user->getKey())->exists()) {
                $updates['username'] = $adminUsername;
            }

            $user->fill($updates)->save();
        }

        // Assign all permissions to the Super Admin role
        $permissions = Permission::pluck('id','id')->all();
        $role->syncPermissions($permissions);

        // Assign the Super Admin role to the user if not already assigned
        if (!$user->hasRole($role->name)) {
            $user->assignRole($role->name);
        }
        
    }
}
