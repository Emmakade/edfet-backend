<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters: roles -> school -> basic reference -> sample data
        $this->call([
            \Database\Seeders\RolesAndPermissionsSeeder::class,
            \Database\Seeders\SchoolSeeder::class,
            \Database\Seeders\SubjectsSeeder::class,
            \Database\Seeders\GradeBoundariesSeeder::class,
            \Database\Seeders\SampleDataSeeder::class,
            \Database\Seeders\ClassSummarySeeder::class,
        ]);
    }
}
