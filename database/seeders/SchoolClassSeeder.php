<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        // Create school classes if none exist
        if (SchoolClass::count() === 0) {
            SchoolClass::insert([
                [
                    'name' => 'Primary 1',
                    'level' => 'Primary',
                    'section' => 'A',
                    'school_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Primary 2',
                    'level' => 'Primary',
                    'section' => 'A',
                    'school_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Primary 3',
                    'level' => 'Primary',
                    'section' => 'A',
                    'school_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Primary 4',
                    'level' => 'Primary',
                    'section' => 'A',
                    'school_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Primary 5',
                    'level' => 'Primary',
                    'section' => 'A',
                    'school_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                
            ]);
        }

        $this->command->info('✅ SchoolClassSeeder completed successfully.');
    }
}