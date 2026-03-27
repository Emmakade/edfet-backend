<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{Student, SchoolClass, Session, Term};
use Illuminate\Foundation\Testing\RefreshDatabase;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_card_pdf_download()
    {
        $student = Student::factory()->create();
        $class = SchoolClass::factory()->create();
        $term = Term::factory()->create();
        $session = Session::factory()->create();

        $response = $this->getJson("/api/reports/{$student->id}?class_id={$class->id}&term_id={$term->id}&session_id={$session->id}");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
