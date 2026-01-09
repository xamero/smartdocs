<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create test users with different roles
        \App\Models\User::updateOrCreate(
            ['email' => 'encoder@example.com'],
            [
                'name' => 'Encoder User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'encoder',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'approver@example.com'],
            [
                'name' => 'Approver User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'approver',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'viewer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Seed offices first
        $this->call(OfficeSeeder::class);

        // Assign users to offices
        $offices = \App\Models\Office::all();
        if ($offices->isNotEmpty()) {
            \App\Models\User::all()->each(function ($user) use ($offices) {
                $user->update(['office_id' => $offices->random()->id]);
            });
        }

        // Seed documents
        $this->call(DocumentSeeder::class);
    }
}
