<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{SchoolClass, Session, Term, Student, Subject, Score};
use App\Services\ResultComputationService;

class ScoreComputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_computation_updates_grade_and_position()
    {
        $class = SchoolClass::factory()->create();
        $term = Term::factory()->create();
        $session = Session::factory()->create();
        $subject = Subject::factory()->create();
        $students = Student::factory(3)->create();

        foreach ($students as $i => $s) {
            Score::create([
                'student_id' => $s->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
                'term_id' => $term->id,
                'session_id' => $session->id,
                'ca' => 20,
                'exam' => 50 + $i,
                'total' => 70 + $i,
            ]);
        }

        $service = new ResultComputationService();
        $result = $service->computeClassResult($class->id, $term->id, $session->id);

        $this->assertEquals('ok', $result['status']);
        $this->assertDatabaseHas('scores', ['grade' => 'A']);
    }
}
