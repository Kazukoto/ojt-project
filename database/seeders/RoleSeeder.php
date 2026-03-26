<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id' => 1,
                'role_name' => 'Admin',
                'description' => 'System Administrator with full access',
                'hourly_rate' => 500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'role_name' => 'HR',
                'description' => 'Human Resources Manager',
                'hourly_rate' => 400.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'role_name' => 'Timekeeper',
                'description' => 'Timekeeper for attendance tracking',
                'hourly_rate' => 300.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'role_name' => 'Finance',
                'description' => 'Finance Manager',
                'hourly_rate' => 450.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'role_name' => 'Engineer',
                'description' => 'Engineer/Technician',
                'hourly_rate' => 350.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}