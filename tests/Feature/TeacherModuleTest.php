<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SessionModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_create_teacher_and_assign_class_teacher(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        Sanctum::actingAs($admin);

        $school = School::create(['name' => 'Demo School']);
        $schoolClass = SchoolClass::create([
            'name' => 'JSS 1A',
            'school_id' => $school->id,
        ]);

        $teacherResponse = $this->postJson('/api/teachers', [
            'name' => 'Grace Teacher',
            'email' => 'grace.teacher@example.com',
            'phone' => '08030000000',
            'password' => 'password',
            'roles' => ['class-teacher', 'subject-teacher'],
        ]);

        $teacherResponse->assertCreated()
            ->assertJsonPath('data.email', 'grace.teacher@example.com');

        $teacherId = $teacherResponse->json('data.id');

        $this->postJson("/api/teachers/{$teacherId}/class-teacher", [
            'school_class_id' => $schoolClass->id,
        ])->assertOk()
            ->assertJsonPath('data.class_teacher.id', $teacherId);

        $this->assertDatabaseHas('school_classes', [
            'id' => $schoolClass->id,
            'class_teacher_id' => $teacherId,
        ]);
    }

    public function test_subject_teacher_can_enter_scores_only_for_assigned_subject(): void
    {
        $school = School::create(['name' => 'Demo School']);
        $schoolClass = SchoolClass::create([
            'name' => 'SS 1A',
            'school_id' => $school->id,
        ]);
        $session = SessionModel::create([
            'name' => '2025/2026',
            'active' => true,
        ]);
        $term = Term::create([
            'name' => 'First Term',
            'session_id' => $session->id,
        ]);
        $assessment = Assessment::create([
            'name' => 'CA 1',
            'type' => 'ca',
            'max_score' => 20,
            'weight' => 1,
        ]);
        $subject = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MTH',
        ]);
        $teacher = User::factory()->create();
        $teacher->assignRole('subject-teacher');
        $student = Student::create([
            'first_name' => 'JOHN',
            'surname' => 'DOE',
            'school_class_id' => $schoolClass->id,
        ]);
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
        ]);

        ClassSubject::create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
            'teacher_id' => $teacher->id,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson('/api/teacher/me/scores/bulk', [
            'term_id' => $term->id,
            'session_id' => $session->id,
            'school_class_id' => $schoolClass->id,
            'scores' => [
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_id' => $subject->id,
                    'assessment_id' => $assessment->id,
                    'score' => 18,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('saved_count', 1);
    }

    public function test_class_teacher_can_enter_attendance_for_assigned_class(): void
    {
        $school = School::create(['name' => 'Demo School']);
        $teacher = User::factory()->create();
        $teacher->assignRole('class-teacher');

        $schoolClass = SchoolClass::create([
            'name' => 'Primary 4',
            'school_id' => $school->id,
            'class_teacher_id' => $teacher->id,
        ]);
        $session = SessionModel::create([
            'name' => '2025/2026',
            'active' => true,
        ]);
        $term = Term::create([
            'name' => 'First Term',
            'session_id' => $session->id,
        ]);
        $student = Student::create([
            'first_name' => 'MARY',
            'surname' => 'JANE',
            'school_class_id' => $schoolClass->id,
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson('/api/teacher/me/attendances', [
            'student_id' => $student->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
            'times_school_opened' => 50,
            'times_present' => 47,
        ])->assertOk()
            ->assertJsonPath('times_present', 47);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'session_id' => $session->id,
            'term_id' => $term->id,
            'times_present' => 47,
        ]);
    }
}
