<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\RemarkService;
use App\Services\ResultBuilderService;
use App\Services\ResultComputationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
            ->with(['student', 'schoolClass'])
            ->findOrFail((int) $validated['enrollment_id']);

        $this->recomputeEnrollmentResults($enrollment, (int) $validated['term_id']);

        $report = $this->resultBuilderService->buildStudentReport(
            (int) $validated['enrollment_id'],
            (int) $validated['term_id']
        );

        $remark = $this->remarkService->getOrGenerateRemark(
            (int) $validated['enrollment_id'],
            (int) $validated['term_id']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Report card loaded successfully',
            'data' => [
                'student' => $report['student'],
                'enrollment' => $report['enrollment'],
                'subjects' => $report['subjects'],
                'result' => $report['result'],
                'remark' => $remark,
            ],
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $enrollment = Enrollment::query()
            ->with(['student', 'schoolClass'])
            ->findOrFail((int) $validated['enrollment_id']);

        $this->recomputeEnrollmentResults($enrollment, (int) $validated['term_id']);

        $report = $this->resultBuilderService->buildStudentReport(
            (int) $validated['enrollment_id'],
            (int) $validated['term_id']
        );

        $remark = $this->remarkService->getOrGenerateRemark(
            (int) $validated['enrollment_id'],
            (int) $validated['term_id']
        );

        $pdf = Pdf::loadView('report_cards.student', [
            'student' => $report['student'],
            'enrollment' => $report['enrollment'],
            'subjects' => $report['subjects'],
            'result' => $report['result'],
            'remark' => $remark,
        ])->setPaper('a4', 'portrait');

        $studentName = $this->safeFileName(
            $report['student']->full_name ?? trim(
                ($report['student']->first_name ?? '') . ' ' .
                ($report['student']->middle_name ?? '') . ' ' .
                ($report['student']->surname ?? '')
            )
        );

        $fileName = 'report_card_' . $studentName . '_term_' . (int) $validated['term_id'] . '.pdf';

        return $pdf->download($fileName);
    }

    public function previewPdf(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $enrollment = Enrollment::query()
            ->with(['student', 'schoolClass'])
            ->findOrFail((int) $validated['enrollment_id']);

        $this->recomputeEnrollmentResults($enrollment, (int) $validated['term_id']);

        $report = $this->resultBuilderService->buildStudentReport(
            (int) $validated['enrollment_id'],
            (int) $validated['term_id']
        );

        $remark = $this->remarkService->getOrGenerateRemark(
            (int) $validated['enrollment_id'],
            (int) $validated['term_id']
        );

        $pdf = Pdf::loadView('report_cards.student', [
            'student' => $report['student'],
            'enrollment' => $report['enrollment'],
            'subjects' => $report['subjects'],
            'result' => $report['result'],
            'remark' => $remark,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('report_card_preview.pdf');
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