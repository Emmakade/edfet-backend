<?php

namespace Tests\Feature;

use App\Imports\StudentsImport;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SessionModel;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAccountProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_create_student_can_create_linked_user_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        Sanctum::actingAs($admin);

        $school = School::create(['name' => 'Demo School']);
        $schoolClass = SchoolClass::create([
            'name' => 'JSS 2A',
            'school_id' => $school->id,
        ]);
        $session = SessionModel::create([
            'name' => '2025/2026',
            'active' => true,
        ]);

        $response = $this->postJson('/api/students/create', [
            'first_name' => 'Janet',
            'surname' => 'Stone',
            'school_class_id' => $schoolClass->id,
            'session_id' => $session->id,
            'login_email' => 'janet.stone@example.com',
            'create_login_account' => true,
            'login_password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.login_email', 'janet.stone@example.com')
            ->assertJsonPath('data.user.email', 'janet.stone@example.com');

        $studentId = $response->json('data.id');

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'login_email' => 'janet.stone@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'janet.stone@example.com',
        ]);
    }

    public function test_reset_password_can_provision_missing_student_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        Sanctum::actingAs($admin);

        $student = Student::create([
            'first_name' => 'PAUL',
            'surname' => 'ADE',
            'admission_number' => 'GNP/2026/0001',
        ]);

        $response = $this->postJson("/api/student-accounts/{$student->id}/reset-password", [
            'password' => 'newpass123',
            'email_domain' => 'students.demo',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.student_id', $student->id)
            ->assertJsonPath('data.new_password', 'newpass123');

        $student->refresh();

        $this->assertNotNull($student->user_id);
        $this->assertSame($student->login_email, $response->json('data.login_email'));
        $this->assertStringEndsWith('@students.demo', $student->login_email);
        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'email' => $student->login_email,
        ]);
    }

    public function test_students_import_can_generate_and_link_accounts_when_requested(): void
    {
        $school = School::create(['name' => 'Demo School']);
        $schoolClass = SchoolClass::create([
            'name' => 'Primary 5',
            'school_id' => $school->id,
        ]);
        $session = SessionModel::create([
            'name' => '2025/2026',
            'active' => true,
        ]);

        $import = new StudentsImport($session->id, $schoolClass->id, true, 'school.test');

        $student = $import->model([
            'first_name' => 'Musa',
            'surname' => 'Ali',
            'gender' => 'male',
        ]);

        $this->assertNotNull($student);

        $student->refresh();

        $this->assertNotNull($student->user_id);
        $this->assertNotNull($student->login_email);
        $this->assertStringEndsWith('@school.test', $student->login_email);
        $this->assertSame(true, $import->successRows[0]['user_linked']);
        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'email' => $student->login_email,
        ]);
    }
}
