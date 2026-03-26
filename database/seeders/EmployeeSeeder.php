<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ── Skip usernames that already exist ─────────────────
        $existingUsernames = DB::table('employees')
            ->pluck('username')
            ->map(fn($u) => strtolower($u))
            ->toArray();

        // ── Real Filipino names ───────────────────────────────
        $people = [
            ['first_name' => 'Jose',      'middle_name' => 'Rizal',        'last_name' => 'Santos',      'gender' => 'Male'],
            ['first_name' => 'Maria',     'middle_name' => 'Clara',        'last_name' => 'Reyes',       'gender' => 'Female'],
            ['first_name' => 'Eduardo',   'middle_name' => 'Jose',         'last_name' => 'Villanueva',  'gender' => 'Male'],
            ['first_name' => 'Cristina',  'middle_name' => 'Bautista',     'last_name' => 'Mendoza',     'gender' => 'Female'],
            ['first_name' => 'Ricardo',   'middle_name' => 'Aquino',       'last_name' => 'Ramos',       'gender' => 'Male'],
            ['first_name' => 'Lourdes',   'middle_name' => 'Natividad',    'last_name' => 'Castillo',    'gender' => 'Female'],
            ['first_name' => 'Fernando',  'middle_name' => 'Aguilar',      'last_name' => 'Soriano',     'gender' => 'Male'],
            ['first_name' => 'Teresita',  'middle_name' => 'Moral',        'last_name' => 'Pascual',     'gender' => 'Female'],
            ['first_name' => 'Rodrigo',   'middle_name' => 'Dizon',        'last_name' => 'Aquino',      'gender' => 'Male'],
            ['first_name' => 'Danilo',    'middle_name' => 'Vergara',      'last_name' => 'Mercado',     'gender' => 'Male'],
            ['first_name' => 'Rowena',    'middle_name' => 'Imperial',     'last_name' => 'Santiago',    'gender' => 'Female'],
            ['first_name' => 'Ernesto',   'middle_name' => 'Baluyot',      'last_name' => 'Fernandez',   'gender' => 'Male'],
            ['first_name' => 'Carmelita', 'middle_name' => 'Guevarra',     'last_name' => 'Bautista',    'gender' => 'Female'],
            ['first_name' => 'Armando',   'middle_name' => 'Sulit',        'last_name' => 'Navarro',     'gender' => 'Male'],
            ['first_name' => 'Alfredo',   'middle_name' => 'Padilla',      'last_name' => 'Ocampo',      'gender' => 'Male'],
            ['first_name' => 'Remedios',  'middle_name' => 'Tan',          'last_name' => 'Legaspi',     'gender' => 'Female'],
            ['first_name' => 'Victorino', 'middle_name' => 'Macapagal',    'last_name' => 'Domingo',     'gender' => 'Male'],
            ['first_name' => 'Bernardo',  'middle_name' => 'Cordero',      'last_name' => 'Cabrera',     'gender' => 'Male'],
            ['first_name' => 'Rogelio',   'middle_name' => 'Encarnacion',  'last_name' => 'Evangelista', 'gender' => 'Male'],
            ['first_name' => 'Simplicio', 'middle_name' => 'Carbonell',    'last_name' => 'Aguilar',     'gender' => 'Male'],
            ['first_name' => 'Milagros',  'middle_name' => 'Lacson',       'last_name' => 'Guerrero',    'gender' => 'Female'],
            ['first_name' => 'Renaldo',   'middle_name' => 'Buenaventura', 'last_name' => 'Villar',      'gender' => 'Male'],
            ['first_name' => 'Glorioso',  'middle_name' => 'Dela Cruz',    'last_name' => 'Torres',      'gender' => 'Male'],
            ['first_name' => 'Natividad', 'middle_name' => 'Rosas',        'last_name' => 'Tolentino',   'gender' => 'Female'],
            ['first_name' => 'Consuelo',  'middle_name' => 'Espiritu',     'last_name' => 'Manalo',      'gender' => 'Female'],
        ];

        // ── Field roles matching your allowanceMap ────────────
        // ⚠️  Adjust role_id values to match what's actually in your roles table
        $fieldRoleMap = [
            'Leadman'      => ['role_id' => 5,  'rate' => 650],
            'Mason'        => ['role_id' => 6,  'rate' => 600],
            'Carpenter'    => ['role_id' => 7,  'rate' => 600],
            'Plumber'      => ['role_id' => 8,  'rate' => 620],
            'Laborer'      => ['role_id' => 9,  'rate' => 500],
            'Painter'      => ['role_id' => 10, 'rate' => 550],
            'Warehouseman' => ['role_id' => 11, 'rate' => 550],
            'Driver'       => ['role_id' => 12, 'rate' => 580],
            'Truck Helper' => ['role_id' => 13, 'rate' => 500],
            'Welder'       => ['role_id' => 14, 'rate' => 650],
            'Engineer'     => ['role_id' => 15, 'rate' => 800],
            'Foreman'      => ['role_id' => 16, 'rate' => 700],
        ];

        $positions = array_keys($fieldRoleMap);
        $projects  = [1, 2, 3, 4];

        $barangays = [
            'Barangay Bagong Silang', 'Barangay San Isidro',
            'Barangay Sta. Cruz',     'Barangay Poblacion',
            'Barangay Maligaya',      'Barangay Masagana',
        ];
        $cities    = ['Cabanatuan City', 'Quezon City', 'Manila', 'Marikina', 'Pasig'];
        $provinces = ['Nueva Ecija', 'Metro Manila', 'Cavite', 'Laguna', 'Bulacan'];
        $puroks    = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5'];

        $ecNames = [
            ['first_name' => 'Gloria',  'last_name' => 'Reyes',   'middle_name' => 'Santos'],
            ['first_name' => 'Manuel',  'last_name' => 'Torres',  'middle_name' => 'Cruz'],
            ['first_name' => 'Corazon', 'last_name' => 'Rosa',    'middle_name' => 'Dela'],
            ['first_name' => 'Renato',  'last_name' => 'Alvarez', 'middle_name' => 'Gomez'],
            ['first_name' => 'Nora',    'last_name' => 'Villar',  'middle_name' => 'Buenaventura'],
        ];

        $seeded = 0;
        $index  = 0;

        foreach ($projects as $projectId) {
            for ($i = 0; $i < 5; $i++) {
                $person   = $people[$index % count($people)];
                $position = $positions[$index % count($positions)];
                $roleData = $fieldRoleMap[$position];
                $ec       = $ecNames[$i % count($ecNames)];

                // Generate a unique username: firstname + last 2 of lastname + index
                $baseUsername = strtolower($person['first_name']) . strtolower(substr($person['last_name'], 0, 3)) . ($index + 1);
                $username     = $baseUsername;
                $suffix       = 1;
                while (in_array(strtolower($username), $existingUsernames)) {
                    $username = $baseUsername . $suffix++;
                }
                $existingUsernames[] = strtolower($username); // Reserve it

                Employee::create([
                    'project_id'        => $projectId,
                    'role_id'           => $roleData['role_id'],
                    'first_name'        => $person['first_name'],
                    'middle_name'       => $person['middle_name'],
                    'last_name'         => $person['last_name'],
                    'suffixes'          => null,
                    'username'          => $username,
                    'password'          => bcrypt('password123'),
                    'contact_number'    => '09' . rand(100000000, 999999999),
                    'birthdate'         => Carbon::create(rand(1975, 1998), rand(1, 12), rand(1, 28))->toDateString(),
                    'gender'            => $person['gender'],
                    'position'          => $position,
                    'rating'            => $roleData['rate'],
                    'status'            => 'active',
                    'house_number'      => (string) rand(1, 999),
                    'purok'             => $puroks[array_rand($puroks)],
                    'barangay'          => $barangays[array_rand($barangays)],
                    'city'              => $cities[array_rand($cities)],
                    'province'          => $provinces[array_rand($provinces)],
                    'zip_code'          => (string) rand(1000, 9999),

                    // Emergency Contact
                    'first_name_ec'     => $ec['first_name'],
                    'middle_name_ec'    => $ec['middle_name'],
                    'last_name_ec'      => $ec['last_name'],
                    'email_ec'          => strtolower($ec['first_name']) . rand(10, 99) . '@gmail.com',
                    'contact_number_ec' => '09' . rand(100000000, 999999999),
                    'house_number_ec'   => (string) rand(1, 999),
                    'purok_ec'          => $puroks[array_rand($puroks)],
                    'barangay_ec'       => $barangays[array_rand($barangays)],
                    'city_ec'           => $cities[array_rand($cities)],
                    'province_ec'       => $provinces[array_rand($provinces)],
                    'country_ec'        => 'Philippines',
                    'zip_code_ec'       => (string) rand(1000, 9999),
                ]);

                $seeded++;
                $index++;
            }
        }

        $this->command->info("✅ {$seeded} employees seeded with real Filipino names!");
        $this->command->info('   Default password for all: password123');
    }
}