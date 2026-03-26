<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashAdvanceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cash_advances')->insert([
            [
                'last_name'   => 'Arana',
                'first_name'  => 'Jocel',
                'middle_name' => 1, // since middle_name is INT in your table
                'amount'      => 5000,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'last_name'   => 'Velancio',
                'first_name'  => 'Alexandria',
                'middle_name' => 2,
                'amount'      => 3000,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'last_name'   => 'Dela Cruz',
                'first_name'  => 'Juan',
                'middle_name' => 3,
                'amount'      => 10000,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
        ]);
    }
}
