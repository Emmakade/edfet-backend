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
            ['first_name'=>'John','last_name'=>'Doe','gender'=>'male','admission_number'=>'ADM001'],
            ['first_name'=>'Jane','last_name'=>'Smith','gender'=>'female','admission_number'=>'ADM002'],
            ['first_name'=>'Bob','last_name'=>'Johnson','gender'=>'male','admission_number'=>'ADM003'],
        ];

        foreach ($students as $index => $s) {
            $student = Student::firstOrCreate(['admission_number'=>$s['admission_number']], array_merge($s, ['school_class_id' => $class->id]));
            Enrollment::firstOrCreate(['student_id'=>$student->id,'session_id'=>$session->id], ['school_class_id'=>$class->id,'active'=>true]);
        }

        // create a sample scores set (partial) for each student on one subject to test
        $subject = Subject::first();
        foreach (Student::where('school_class_id', $class->id)->get() as $student) {
            Score::updateOrCreate(
                [
                    'student_id'=>$student->id,
                    'subject_id'=>$subject->id,
                    'school_class_id'=>$student->school_class_id,
                    'term_id'=>$term->id,
                    'session_id'=>$session->id
                ],
                [
                    'ca_score' => rand(10,30),
                    'exam_score' => rand(30,70),
                    'total' => 0, // will be computed later by Step 3's service
                ]
            );
        }
    }
}
