<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectSeeder extends Seeder
{
    public function run()
    {
        $projects = [
            [
                'name' => 'Building A Construction',
                'description' => 'Main building construction project',
            ],
            [
                'name' => 'Road Expansion',
                'description' => 'Highway road expansion project',
            ],
            [
                'name' => 'Bridge Renovation',
                'description' => 'Old bridge renovation and repair',
            ],
            [
                'name' => 'Mall Development',
                'description' => 'Shopping mall construction',
            ],
            [
                'name' => 'Residential Complex',
                'description' => 'Multi-unit residential building',
            ],
        ];

        foreach ($projects as $project) {
            Project::create($project);
        }
    }
}