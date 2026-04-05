<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\SessionModel;
use App\Models\Subject;

class AssessmentSeeder extends Seeder{
    public function run(): void
    {
        
        // Create assessments if none exist
        if (Assessment::count() === 0) {
            Assessment::insert([
                [
                    'name' => 'HA',
                    'type' => 'ca',
                    'max_score' => 10,
                    'weight' => 1
                ],
                [
                    'name' => 'TEST',
                    'type' => 'ca',
                    'max_score' => 30,
                    'weight' => 1
                ],
                [
                    'name' => 'EXAM',
                    'type' => 'exam',
                    'max_score' => 60,
                    'weight' => 1
                ],
            ]);
        }

        $this->command->info('✅ AssessmentSeeder completed successfully.');
    }
}