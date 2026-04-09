<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\Remark;
use App\Services\RemarkService;
use App\Services\ResultBuilderService;
use App\Services\ResultComputationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ZipArchive;

class ReportCardController extends Controller
{
    protected ResultBuilderService $resultBuilderService;
    protected ResultComputationService $resultComputationService;
    protected RemarkService $remarkService;

    public function __construct(
        ResultBuilderService $resultBuilderService,
        ResultComputationService $resultComputationService,
        RemarkService $remarkService
    ) {
        $this->resultBuilderService = $resultBuilderService;
        $this->resultComputationService = $resultComputationService;
        $this->remarkService = $remarkService;
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $enrollment = Enrollment::query()
            ->with(['student', 'schoolClass', 'session'])
            ->findOrFail((int) $validated['enrollment_id']);

        $this->recomputeEnrollmentResults($enrollment, (int) $validated['term_id']);

        return response()->json([
            'status' => 'success',
            'message' => 'Report card loaded successfully',
            'data' => $this->buildReportPayload(
                $enrollment,
                (int) $validated['term_id']
            ),
        ]);
    }

    public function previewPdf(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $enrollment = Enrollment::query()
            ->with(['student', 'schoolClass', 'session'])
            ->findOrFail((int) $validated['enrollment_id']);

        $this->recomputeEnrollmentResults($enrollment, (int) $validated['term_id']);

        return $this->renderPdfResponse($enrollment, (int) $validated['term_id'], false);
    }

    public function downloadPdf(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $enrollment = Enrollment::query()
            ->with(['student', 'schoolClass', 'session'])
            ->findOrFail((int) $validated['enrollment_id']);

        $this->recomputeEnrollmentResults($enrollment, (int) $validated['term_id']);

        return $this->renderPdfResponse($enrollment, (int) $validated['term_id'], true);
    }

    public function downloadClassPdf(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $term = Term::query()->findOrFail((int) $validated['term_id']);
        $schoolClass = SchoolClass::query()->findOrFail((int) $validated['school_class_id']);

        $enrollments = Enrollment::query()
            ->with(['student', 'schoolClass', 'session'])
            ->where('school_class_id', (int) $validated['school_class_id'])
            ->where('session_id', (int) $term->session_id)
            ->whereHas('student')
            ->get()
            ->sortBy(function (Enrollment $enrollment) {
                $student = $enrollment->student;

                return strtolower(trim(implode(' ', array_filter([
                    $student?->surname,
                    $student?->first_name,
                    $student?->middle_name,
                ]))));
            })
            ->values();

        if ($enrollments->isEmpty()) {
            return response()->json([
                'message' => 'No students were found in this class for the selected term session.',
            ], 404);
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'report_cards_');

        if ($zipPath === false) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Unable to prepare the report card archive.');
        }

        $archivePath = $zipPath . '.zip';

        if (! @rename($zipPath, $archivePath)) {
            @unlink($zipPath);
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Unable to prepare the report card archive.');
        }

        $zip = new ZipArchive();
        $opened = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            @unlink($archivePath);
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Unable to create the report card archive.');
        }

        foreach ($enrollments as $enrollment) {
            $this->recomputeEnrollmentResults($enrollment, (int) $term->id);

            $zip->addFromString(
                $this->buildStudentReportFileName($enrollment, (int) $term->id),
                $this->buildPdfBinary($enrollment, (int) $term->id)
            );
        }

        $zip->close();

        $archiveName = sprintf(
            'report_cards_%s_term_%d.zip',
            $this->safeFileName($schoolClass->name),
            (int) $term->id
        );

        return response()->download($archivePath, $archiveName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function myContext(Request $request)
    {
        $student = $request->user()->student;

        if (! $student) {
            return response()->json([
                'message' => 'No student profile is linked to this user.',
            ], 404);
        }

        $currentTerm = Term::query()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();

        $preferredSessionId = $currentTerm?->session_id;

        $enrollment = $this->resolveStudentEnrollment(
            $student->id,
            $preferredSessionId
        );

        if (! $enrollment) {
            return response()->json([
                'message' => 'No enrollment found for this student.',
            ], 404);
        }

        $terms = Term::query()
            ->where('session_id', $enrollment->session_id)
            ->orderBy('starts_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'student' => $student->fresh(),
                'enrollment' => $enrollment->load(['schoolClass', 'session']),
                'current_term' => $currentTerm && (int) $currentTerm->session_id === (int) $enrollment->session_id
                    ? $currentTerm
                    : $terms->last(),
                'terms' => $terms,
            ],
        ]);
    }

    public function myReport(Request $request)
    {
        $validated = $request->validate([
            'term_id' => ['nullable', 'exists:terms,id'],
        ]);

        $student = $request->user()->student;

        if (! $student) {
            return response()->json([
                'message' => 'No student profile is linked to this user.',
            ], 404);
        }

        $term = $this->resolveTerm($validated['term_id'] ?? null);

        if (! $term) {
            return response()->json([
                'message' => 'No current term found. Please select a valid term.',
            ], 422);
        }

        $enrollment = $this->resolveStudentEnrollment($student->id, (int) $term->session_id);

        if (! $enrollment) {
            return response()->json([
                'message' => 'No enrollment found for this student in the selected term session.',
            ], 404);
        }

        $this->recomputeEnrollmentResults($enrollment, (int) $term->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Student report card loaded successfully',
            'data' => $this->buildReportPayload($enrollment, (int) $term->id),
        ]);
    }

    public function myPreviewPdf(Request $request)
    {
        $validated = $request->validate([
            'term_id' => ['nullable', 'exists:terms,id'],
        ]);

        $student = $request->user()->student;

        if (! $student) {
            return response()->json([
                'message' => 'No student profile is linked to this user.',
            ], 404);
        }

        $term = $this->resolveTerm($validated['term_id'] ?? null);

        if (! $term) {
            return response()->json([
                'message' => 'No current term found. Please select a valid term.',
            ], 422);
        }

        $enrollment = $this->resolveStudentEnrollment($student->id, (int) $term->session_id);

        if (! $enrollment) {
            return response()->json([
                'message' => 'No enrollment found for this student in the selected term session.',
            ], 404);
        }

        $this->recomputeEnrollmentResults($enrollment, (int) $term->id);

        return $this->renderPdfResponse($enrollment, (int) $term->id, false);
    }

    public function myDownloadPdf(Request $request)
    {
        $validated = $request->validate([
            'term_id' => ['nullable', 'exists:terms,id'],
        ]);

        $student = $request->user()->student;

        if (! $student) {
            return response()->json([
                'message' => 'No student profile is linked to this user.',
            ], 404);
        }

        $term = $this->resolveTerm($validated['term_id'] ?? null);

        if (! $term) {
            return response()->json([
                'message' => 'No current term found. Please select a valid term.',
            ], 422);
        }

        $enrollment = $this->resolveStudentEnrollment($student->id, (int) $term->session_id);

        if (! $enrollment) {
            return response()->json([
                'message' => 'No enrollment found for this student in the selected term session.',
            ], 404);
        }

        $this->recomputeEnrollmentResults($enrollment, (int) $term->id);

        return $this->renderPdfResponse($enrollment, (int) $term->id, true);
    }

    private function buildReportPayload(Enrollment $enrollment, int $termId): array
    {
        $report = $this->resultBuilderService->buildStudentReport(
            (int) $enrollment->id,
            $termId
        );

        $remark = $this->remarkService->getOrGenerateRemark(
            (int) $enrollment->id,
            $termId
        );

        $term = Term::query()->find($termId);

        $reportEnrollment = $report['enrollment']->loadMissing(['schoolClass.school', 'session']);

        $attendance = Attendance::where('student_id', $enrollment->student_id)
            ->where('session_id', $enrollment->session_id)
            ->where('term_id', $termId)
            ->first();

        return [
            'student' => $report['student'],
            'enrollment' => $reportEnrollment,
            'subjects' => $report['subjects'],
            'result' => $report['result'],
            'remark' => $remark,
            'term' => $term,
            'attendance' => $attendance,
        ];
    }

    private function renderPdfResponse(Enrollment $enrollment, int $termId, bool $download = false)
    {
        $pdf = $this->buildPdfDocument($enrollment, $termId);
        $fileName = $this->buildStudentReportFileName($enrollment, $termId);

        return $download
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    private function buildPdfDocument(Enrollment $enrollment, int $termId)
    {
        $payload = $this->buildReportPayload($enrollment, $termId);

        return Pdf::loadView('pdf.report_card', [
            'student' => $payload['student'],
            'enrollment' => $payload['enrollment'],
            'subjects' => $payload['subjects'],
            'result' => $payload['result'],
            'remark' => $payload['remark'],
            'term' => $payload['term'],
            'attendance' => $payload['attendance'],
        ])->setPaper('a4', 'portrait');
    }

    private function buildPdfBinary(Enrollment $enrollment, int $termId): string
    {
        return $this->buildPdfDocument($enrollment, $termId)->output();
    }

    private function buildStudentReportFileName(Enrollment $enrollment, int $termId): string
    {
        $student = $enrollment->student;

        $studentName = $this->safeFileName(
            $student?->full_name ?? trim(
                ($student?->first_name ?? '') . ' ' .
                ($student?->middle_name ?? '') . ' ' .
                ($student?->surname ?? '')
            )
        );

        return 'report_card_' . $studentName . '_term_' . $termId . '.pdf';
    }

    private function resolveStudentEnrollment(int $studentId, ?int $sessionId = null): ?Enrollment
    {
        return Enrollment::query()
            ->with(['student', 'schoolClass', 'session'])
            ->where('student_id', $studentId)
            ->when(
                $sessionId,
                fn ($q) => $q->where('session_id', $sessionId)
            )
            ->orderByDesc('session_id')
            ->orderByDesc('id')
            ->first();
    }

    private function resolveTerm(?int $termId = null): ?Term
    {
        if ($termId) {
            return Term::query()->find($termId);
        }

        return Term::query()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();
    }

    private function recomputeEnrollmentResults(Enrollment $enrollment, int $termId): void
    {
        $subjectIds = $enrollment->schoolClass
            ? $enrollment->schoolClass
                ->classSubjects()
                ->where('session_id', $enrollment->session_id)
                ->pluck('subject_id')
            : collect();

        foreach ($subjectIds as $subjectId) {
            $this->resultComputationService->computeSubjectResult(
                (int) $enrollment->id,
                (int) $subjectId,
                $termId,
                (int) $enrollment->session_id
            );
        }

        $this->resultComputationService->computeClassSubjectStats(
            (int) $enrollment->school_class_id,
            $termId,
            (int) $enrollment->session_id
        );

        $this->resultComputationService->computeOverallResults(
            (int) $enrollment->school_class_id,
            $termId,
            (int) $enrollment->session_id
        );
    }

    private function safeFileName(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'student';
        }

        $value = preg_replace('/[^A-Za-z0-9\-_]+/', '_', $value);
        $value = preg_replace('/_+/', '_', $value);

        return trim($value, '_') ?: 'student';
    }
}


