<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Score;
use App\Models\School;
use App\Models\SessionModel;
use App\Models\Term;
use App\Models\StudentResult;
use App\Models\SubjectResult;
use App\Models\SchoolClass;
use App\Models\Enrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Services\ResultBuilderService;
use App\Services\RemarkService;

class ReportCardController extends Controller
{
    public function __construct(
        ResultBuilderService $builder,
        RemarkService $remarkService
    ) {
        $this->builder = $builder;
        $this->remarkService = $remarkService;
    }

    public function generate(Request $request, $studentId)
    {
        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'term_id' => 'required|exists:terms,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        $school = School::first();
        $term = Term::findOrFail($data['term_id']);
        $session = SessionModel::findOrFail($data['session_id']);

        if ($term->session_id !== $session->id) {
            return response()->json(['message' => 'Invalid term/session'], 422);
        }

        // ✅ Resolve enrollment
        $enrollment = Enrollment::where([
            'student_id' => $studentId,
            'school_class_id' => $data['school_class_id'],
            'session_id' => $data['session_id'],
        ])->firstOrFail();

        // ✅ Build report data
        $report = $this->builder->buildStudentReport(
            $enrollment->id,
            $data['term_id']
        );

        // ✅ Remarks
        $remark = $this->remarkService->store(
            $enrollment->id,
            $data['term_id']
        );

        $pdf = Pdf::loadView('pdf.report_card', [
            'school' => $school,
            'student' => $report['student'],
            'subjects' => $report['subjects'], // 🔥 key change
            'result' => $report['result'],
            'class' => $report['enrollment']->schoolClass,
            'term' => $term->name,
            'session' => $session->name,
            'attendance' => [
                'opened' => 75,
                'present' => 70,
            ],
            'teacher_remark' => $remark->class_teacher_remark ?? '',
            'head_remark' => $remark->head_teacher_remark ?? '',
            'logo' => public_path('images/logo.png'),
            'next_term' => \Carbon\Carbon::parse($school->next_term_begins)->format('jS F Y'),
        ]);

        return $pdf->download("report-card-{$studentId}.pdf");
    }

    public function generateClass($classId, $termId, $sessionId)
    {
        $school = School::first();
        $term = Term::findOrFail($termId);
        $session = SessionModel::findOrFail($sessionId);

        if ($term->session_id !== $session->id) {
            return response()->json(['message' => 'Invalid term/session'], 422);
        }

        $enrollments = Enrollment::with(['student.user', 'schoolClass'])
            ->where('school_class_id', $classId)
            ->where('session_id', $sessionId)
            ->get();

        if ($enrollments->isEmpty()) {
            abort(404, 'No students found.');
        }

        // 🔥 PRELOAD ALL RESULTS (PERFORMANCE BOOST)
        $subjectResults = SubjectResult::with('subject')
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->where('term_id', $termId)
            ->get()
            ->groupBy('enrollment_id');

        $overallResults = StudentResult::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->where('term_id', $termId)
            ->get()
            ->keyBy('enrollment_id');

        $html = $enrollments->map(function ($enrollment) use (
            $school,
            $subjectResults,
            $overallResults,
            $term,
            $session,
            $termId
        ) {

            $remark = $this->remarkService->store(
                $enrollment->id,
                $termId
            );

            return view('pdf.report_card', [
                'school' => $school,
                'student' => $enrollment->student,
                'subjects' => $subjectResults->get($enrollment->id, collect()),
                'result' => $overallResults->get($enrollment->id),
                'class' => $enrollment->schoolClass,
                'term' => $term->name,
                'session' => $session->name,
                'attendance' => [
                    'opened' => 75,
                    'present' => 70,
                ],
                'teacher_remark' => $remark->class_teacher_remark ?? '',
                'head_remark' => $remark->head_teacher_remark ?? '',
                'logo' => public_path('images/logo.png'),
                'next_term' => \Carbon\Carbon::parse($school->next_term_begins)->format('jS F Y'),
            ])->render();

        })->implode('<div style="page-break-after: always;"></div>');

        return Pdf::loadHTML($html)
            ->download("report_cards_class_{$classId}.pdf");
    }
}


