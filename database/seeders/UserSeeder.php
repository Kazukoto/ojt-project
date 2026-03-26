<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@payroll.local',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role_id' => 1, // Admin
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Timekeeper User',
                'email' => 'timekeeper@payroll.local',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role_id' => 3, // Timekeeper
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HR Manager',
                'email' => 'hr@payroll.local',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role_id' => 2, // HR
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance Manager',
                'email' => 'finance@payroll.local',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role_id' => 4, // Finance
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Engineer',
                'email' => 'engineer@payroll.local',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role_id' => 5, // Engineer
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}