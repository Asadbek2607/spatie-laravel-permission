<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Asadbek',
            'email' => 'admin@admin',
            'password' => bcrypt('password')
        ]);

        // Create the 'Admin' role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'Admin']);

        // Assign permissions to the 'Admin' role
        $permissions = Permission::pluck('name')->all();
        $role->syncPermissions($permissions);

        // Assign the 'Admin' role to the user
        $user->assignRole([$role->name]);

        // assign permissions to admin role
        $role = Role::findByName('Admin');
        $role->givePermissionTo(Permission::all());

    }


}
