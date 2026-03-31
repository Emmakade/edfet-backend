<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassSummary;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\SessionModel;
use App\Models\Subject;

class ClassSummarySeeder extends Seeder
{
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

        // Create class summary if none exists
        if (ClassSummary::count() === 0) {
            ClassSummary::create([
                'school_class_id' => $schoolClass->id,
                'term_id' => $term->id,
                'session_id' => $session->id,
                'subject_id' => $subject->id,
                'average' => rand(40, 80),
                'highest' => rand(70, 100),
                'lowest' => rand(10, 39),
                'computed_at' => now(),
            ]);
        }

        $this->command->info('✅ ClassSummarySeeder completed successfully.');
    }
}
