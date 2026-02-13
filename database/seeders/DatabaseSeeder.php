<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdminRole = \App\Models\Role::create(['name' => 'Super Admin']);
        $adminRole = \App\Models\Role::create(['name' => 'Admin']);
        $userRole = \App\Models\Role::create(['name' => 'User']);

        User::factory()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@crm.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@crm.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'User Biasa',
            'username' => 'user',
            'email' => 'user@crm.com',
            'password' => bcrypt('password'),
            'role_id' => $userRole->id,
            'is_active' => true,
        ]);
    }
}
