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
        // Use existing data or create fallbacks
        $schoolClass = SchoolClass::first() ?? SchoolClass::create([
            'name' => 'Primary 1',
            'level' => 'Primary',
            'section' => 'A',
            'school_id' => 1,
        ]);

        $term = Term::first() ?? Term::create([
            'name' => 'First Term',
        ]);

        $session = SessionModel::first() ?? SessionModel::create([
            'name' => '2024/2025',
            'year_start' => now()->startOfYear(),
            'year_end' => now()->endOfYear(),
            'active' => true,
        ]);

        $subject = Subject::first() ?? Subject::create([
            'name' => 'Mathematics',
        ]);

        // Create assessments if none exist
        if (Assessment::count() === 0) {
            Assessment::insert([
                [
                    'name' => 'CA 1',
                    'type' => 'ca',
                    'term_id' => $term->id,
                    'session_id' => $session->id,
                    'max_score' => 20,
                    'weight' => 1
                ],
                [
                    'name' => 'CA 2',
                    'type' => 'ca',
                    'term_id' => $term->id,
                    'session_id' => $session->id,
                    'max_score' => 20,
                    'weight' => 1
                ],
                [
                    'name' => 'Final Exam',
                    'type' => 'exam',
                    'term_id' => $term->id,
                    'session_id' => $session->id,
                    'max_score' => 60,
                    'weight' => 2
                ],
            ]);
        }

        $this->command->info('✅ AssessmentSeeder completed successfully.');
    }
}