<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed base roles
        $this->call(RolesSeeder::class);

        // Seed initial settings
        $this->call(SettingsSeeder::class);

        // Create a demo user and assign default role
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Assign default role if exists
        if (Role::where('name', 'User')->exists()) {
            $user->assignRole('User');
        }
    }
}
