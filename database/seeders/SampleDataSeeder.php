<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SessionModel;
use App\Models\Term;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\Subject;
use App\Models\Assessment;
use App\Models\User;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // create a sample session & term
        $session = SessionModel::firstOrCreate(['name'=>'2025/2026'], ['year_start' => now()->startOfYear(), 'year_end' => now()->endOfYear(), 'active'=>true]);
        $term = Term::firstOrCreate(['name'=>'First Term','session_id'=>$session->id], ['starts_at'=>now()->subWeeks(4),'ends_at'=>now()->addWeeks(2)]);

        // create a class
        $class = SchoolClass::firstOrCreate(['name'=>'Primary 4','level'=>'Primary','section'=>'A']);

        // attach subjects to class (all existing subjects)
        $subjects = Subject::all();
        $class->subjects()->sync($subjects->pluck('id')->toArray());

        // create sample students
        $students = [
            ['first_name'=>'John','middle_name'=>'Doe','surname'=>'Ray','gender'=>'male','admission_number'=>'ADM001'],
            ['first_name'=>'Jane','middle_name'=>'Smith','surname'=>'Pep','gender'=>'female','admission_number'=>'ADM002'],
            ['first_name'=>'Bob','middle_name'=>'Johnson','surname'=>'Sam','gender'=>'male','admission_number'=>'ADM003'],
            ['first_name'=>'Dell','middle_name'=>'Mark','surname'=>'Dak','gender'=>'female','admission_number'=>'ADM004'],
        ];

        foreach ($students as $index => $s) {
            $student = Student::firstOrCreate(['admission_number'=>$s['admission_number']], array_merge($s, ['school_class_id' => $class->id]));
            Enrollment::firstOrCreate(
                ['student_id'=>$student->id,'session_id'=>$session->id], 
                ['school_class_id'=>$class->id,'status'=>'active','enrolled_at'=>now()]
            );
        }

        // create a sample scores set (partial) for each student on one subject to test
        $subject = Subject::first();
        $assessment = Assessment::first() ?? Assessment::create([
            'name' => 'CA 1',
            'type' => 'ca',
            'max_score' => 20,
            'weight' => 1,
        ]);

        $enrollments = Enrollment::where('school_class_id', $class->id)
            ->where('session_id', $session->id)
            ->get();

        foreach ($enrollments as $enrollment) {
            Score::updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_id' => $subject->id,
                    'assessment_id' => $assessment->id,
                    'term_id' => $term->id,
                    'school_class_id' => $class->id,
                    'session_id' => $session->id,
                ],
                [
                    'score' => 50,
                ]
            );
        }
    }
}
