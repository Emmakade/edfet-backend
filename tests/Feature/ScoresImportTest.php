<?php

namespace Tests\Feature;

use App\Imports\ScoresImport;
use App\Models\Assessment;
use App\Models\GradeBoundary;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\SessionModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ScoresImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_valid_rows_and_keeps_invalid_rows_in_failed_summary(): void
    {
        [$session, $term, $class, $enrollment, $subject, $assessment] = $this->createImportContext();

        GradeBoundary::create([
            'min_score' => 0,
            'max_score' => 100,
            'grade' => 'A',
            'remark' => 'Excellent',
            'priority' => 1,
        ]);

        $path = $this->writeCsv([
            "\xEF\xBB\xBFEnrollment ID,Subject ID,Assessment ID,Score",
            " {$enrollment->id} , {$subject->id} , {$assessment->id} , 74 ",
            "999999,{$subject->id},{$assessment->id},80",
        ]);

        $import = new ScoresImport($term->id, $session->id, $class->id);

        Excel::import($import, $path);
        $import->recomputeTouchedResults();

        $this->assertCount(1, $import->successRows);
        $this->assertCount(1, $import->failedRows);
        $this->assertSame(3, $import->failedRows[0]['row']);

        $this->assertDatabaseHas('scores', [
            'enrollment_id' => $enrollment->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'term_id' => $term->id,
            'session_id' => $session->id,
            'school_class_id' => $class->id,
            'score' => 74,
        ]);

        $this->assertSame(1, Score::count());

        $subjectResult = SubjectResult::where('enrollment_id', $enrollment->id)
            ->where('subject_id', $subject->id)
            ->where('term_id', $term->id)
            ->first();

        $this->assertNotNull($subjectResult);
        $this->assertSame(74, $subjectResult->total);
    }

    public function test_recompute_touched_results_only_uses_scores_from_the_current_term(): void
    {
        [$session, $termOne, $class, $enrollment, $subject, $assessment] = $this->createImportContext();

        $termTwo = Term::create([
            'name' => 'Second Term',
            'session_id' => $session->id,
        ]);

        GradeBoundary::create([
            'min_score' => 0,
            'max_score' => 100,
            'grade' => 'A',
            'remark' => 'Excellent',
            'priority' => 1,
        ]);

        Score::create([
            'enrollment_id' => $enrollment->id,
            'subject_id' => $subject->id,
            'assessment_id' => $assessment->id,
            'term_id' => $termTwo->id,
            'session_id' => $session->id,
            'school_class_id' => $class->id,
            'score' => 40,
        ]);

        $path = $this->writeCsv([
            'enrollment_id,subject_id,assessment_id,score',
            "{$enrollment->id},{$subject->id},{$assessment->id},15",
        ]);

        $import = new ScoresImport($termOne->id, $session->id, $class->id);

        Excel::import($import, $path);
        $import->recomputeTouchedResults();

        $subjectResult = SubjectResult::where('enrollment_id', $enrollment->id)
            ->where('subject_id', $subject->id)
            ->where('term_id', $termOne->id)
            ->first();

        $this->assertNotNull($subjectResult);
        $this->assertSame(15, $subjectResult->total);
    }

    private function createImportContext(): array
    {
        $session = SessionModel::create([
            'name' => '2025/2026',
            'year_start' => '2025-09-01',
            'year_end' => '2026-07-31',
            'active' => true,
        ]);

        $term = Term::create([
            'name' => 'First Term',
            'session_id' => $session->id,
        ]);

        $class = SchoolClass::create([
            'name' => 'JSS 1',
        ]);

        $student = Student::create([
            'surname' => 'Doe',
            'first_name' => 'Jane',
            'admission_number' => 'ADM-' . Str::upper(Str::random(8)),
            'school_class_id' => $class->id,
        ]);

        $enrollment = new \App\Models\Enrollment();
        $enrollment->student_id = $student->id;
        $enrollment->school_class_id = $class->id;
        $enrollment->session_id = $session->id;
        $enrollment->term_id = $term->id;
        $enrollment->status = 'active';
        $enrollment->save();

        $subject = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MTH',
        ]);

        $assessment = Assessment::create([
            'name' => 'CA 1',
            'type' => 'ca',
            'max_score' => 20,
            'weight' => 1,
        ]);

        return [$session, $term, $class, $enrollment, $subject, $assessment];
    }

    private function writeCsv(array $lines): string
    {
        $directory = storage_path('framework/testing/imports');
        File::ensureDirectoryExists($directory);

        $path = $directory . DIRECTORY_SEPARATOR . 'scores-import-' . Str::uuid() . '.csv';

        file_put_contents($path, implode(PHP_EOL, $lines));

        return $path;
    }
}
