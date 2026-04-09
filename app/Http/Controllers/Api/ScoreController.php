<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\ScoresImport;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Score;
use App\Services\ResultComputationService;
use App\Services\TeacherAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ScoreController extends Controller
{
    protected ResultComputationService $resultComputationService;
    protected TeacherAccessService $teacherAccessService;

    public function __construct(
        ResultComputationService $resultComputationService,
        TeacherAccessService $teacherAccessService
    ) {
        $this->resultComputationService = $resultComputationService;
        $this->teacherAccessService = $teacherAccessService;
    }

    public function storeBulkScores(Request $request)
    {
        $this->authorize('enter-scores');

        $payload = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.enrollment_id' => ['required', 'exists:enrollments,id'],
            'scores.*.subject_id' => ['required', 'exists:subjects,id'],
            'scores.*.assessment_id' => ['required', 'exists:assessments,id'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
            'term_id' => ['required', 'exists:terms,id'],
            'session_id' => ['required', 'exists:sessions,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
        ]);

        return DB::transaction(function () use ($payload, $request) {
            $saved = [];
            $failed = [];
            $touchedPairs = [];

            foreach ($payload['scores'] as $item) {
                try {
                    $enrollment = Enrollment::query()
                        ->whereKey($item['enrollment_id'])
                        ->where('school_class_id', $payload['school_class_id'])
                        ->where('session_id', $payload['session_id'])
                        ->first();

                    if (! $enrollment) {
                        throw new \Exception('Enrollment does not belong to the selected class/session');
                    }

                    $subjectBelongsToClass = ClassSubject::query()
                        ->where('school_class_id', $payload['school_class_id'])
                        ->where('session_id', $payload['session_id'])
                        ->where('subject_id', $item['subject_id'])
                        ->exists();

                    if (! $subjectBelongsToClass) {
                        throw new \Exception('Subject is not assigned to the selected class/session');
                    }

                    if (! $this->teacherAccessService->canEnterScores(
                        $request->user(),
                        (int) $payload['school_class_id'],
                        (int) $item['subject_id'],
                        (int) $payload['session_id']
                    )) {
                        throw ValidationException::withMessages([
                            'scores' => ['You are not allowed to enter scores for one or more selected subjects/classes.'],
                        ]);
                    }

                    $assessment = Assessment::query()->findOrFail($item['assessment_id']);
                    $roundedScore = (int) round($item['score']);

                    if ($roundedScore > (int) $assessment->max_score) {
                        throw ValidationException::withMessages([
                            'scores' => ["Score cannot be greater than the assessment max score of {$assessment->max_score}."],
                        ]);
                    }

                    $score = Score::updateOrCreate(
                        [
                            'enrollment_id' => $item['enrollment_id'],
                            'subject_id' => $item['subject_id'],
                            'assessment_id' => $item['assessment_id'],
                            'term_id' => $payload['term_id'],
                            'session_id' => $payload['session_id'],
                            'school_class_id' => $payload['school_class_id'],
                        ],
                        [
                            'score' => $roundedScore,
                        ]
                    );

                    $touchedPairs[$item['enrollment_id'] . ':' . $item['subject_id']] = [
                        'enrollment_id' => (int) $item['enrollment_id'],
                        'subject_id' => (int) $item['subject_id'],
                    ];

                    $saved[] = $score;
                } catch (\Throwable $e) {
                    $failed[] = [
                        'data' => $item,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            foreach ($touchedPairs as $pair) {
                $this->resultComputationService->computeSubjectResult(
                    $pair['enrollment_id'],
                    $pair['subject_id'],
                    (int) $payload['term_id'],
                    (int) $payload['session_id']
                );
            }

            $this->resultComputationService->computeClassSubjectStats(
                (int) $payload['school_class_id'],
                (int) $payload['term_id'],
                (int) $payload['session_id']
            );

            $this->resultComputationService->computeOverallResults(
                (int) $payload['school_class_id'],
                (int) $payload['term_id'],
                (int) $payload['session_id']
            );

            return response()->json([
                'status' => count($failed) > 0
                    ? (count($saved) > 0 ? 'partial_success' : 'failed')
                    : 'completed',
                'message' => 'Bulk score upload processed',
                'saved_count' => count($saved),
                'failed_count' => count($failed),
                'failures' => $failed,
            ], count($saved) === 0 && count($failed) > 0 ? 422 : 200);
        });
    }

    public function show(Score $score)
    {
        if (! $this->teacherAccessService->canViewScore($score, request()->user())) {
            abort(403, 'You are not allowed to view this score.');
        }

        return response()->json(
            $score->load('enrollment.student', 'subject', 'assessment', 'term')
        );
    }

    //method to update bulk scores for a teacher's assigned class and subjects strimline for a class, term an session
    public function update(Request $request, Score $score)
    {
        $this->authorize('enter-scores');
        if (! $this->teacherAccessService->canEnterScores(
            request()->user(),
            (int) $score->school_class_id,
            (int) $score->subject_id,
            (int) $score->session_id
        )) {
            abort(403, 'You are not allowed to update this score.');
        }
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0'],
        ]);

        $score->update($validated);

        return response()->json(['message' => 'Score updated successfully']);
    }

    
    public function destroy(Score $score)
    {
        $this->authorize('enter-scores');

        if (! $this->teacherAccessService->canEnterScores(
            request()->user(),
            (int) $score->school_class_id,
            (int) $score->subject_id,
            (int) $score->session_id
        )) {
            abort(403, 'You are not allowed to delete this score.');
        }

        $classId = (int) $score->school_class_id;
        $termId = (int) $score->term_id;
        $sessionId = (int) $score->session_id;
        $enrollmentId = (int) $score->enrollment_id;
        $subjectId = (int) $score->subject_id;

        $score->delete();

        $this->resultComputationService->computeSubjectResult(
            $enrollmentId,
            $subjectId,
            $termId,
            $sessionId
        );

        $this->resultComputationService->computeClassSubjectStats($classId, $termId, $sessionId);
        $this->resultComputationService->computeOverallResults($classId, $termId, $sessionId);

        return response()->json(['message' => 'Deleted']);
    }

    public function recomputeAll(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'term_id' => 'required|exists:terms,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        $this->resultComputationService->recomputeClassResults(
            (int) $validated['school_class_id'],
            (int) $validated['term_id'],
            (int) $validated['session_id']
        );

        return response()->json([
            'message' => 'All results recomputed successfully.',
        ]);
    }

    public function importScores(Request $request)
    {
        $this->authorize('enter-scores');

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
            'term_id' => 'required|exists:terms,id',
            'session_id' => 'required|exists:sessions,id',
            'school_class_id' => 'required|exists:school_classes,id',
        ]);

        $import = new ScoresImport(
            $validated['term_id'],
            $validated['session_id'],
            $validated['school_class_id']
        );

        if (! $request->user()->hasRole('super-admin')) {
            $assignedSubjectIds = ClassSubject::query()
                ->where('school_class_id', $validated['school_class_id'])
                ->where('session_id', $validated['session_id'])
                ->where('teacher_id', $request->user()->id)
                ->pluck('subject_id');

            if ($assignedSubjectIds->isEmpty() && ! $this->teacherAccessService->isClassTeacherForClass(
                $request->user(),
                (int) $validated['school_class_id']
            )) {
                abort(403, 'You are not allowed to import scores for this class.');
            }
        }

        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();

        Excel::import($import, $uploadedFile);

        $successfulCount = count($import->successRows);
        $failedCount = count($import->failedRows);

        if ($successfulCount > 0) {
            $import->recomputeTouchedResults();

            $this->resultComputationService->computeClassSubjectStats(
                (int) $validated['school_class_id'],
                (int) $validated['term_id'],
                (int) $validated['session_id']
            );

            $this->resultComputationService->computeOverallResults(
                (int) $validated['school_class_id'],
                (int) $validated['term_id'],
                (int) $validated['session_id']
            );
        }

        $status = $failedCount === 0
            ? 'completed'
            : ($successfulCount === 0 ? 'failed' : 'partial_success');

        $message = match ($status) {
            'completed' => 'Scores imported successfully',
            'partial_success' => 'Scores imported with some failed rows',
            default => 'No scores were imported',
        };

        return response()->json([
            'status' => $status,
            'message' => $message,
            'file_name' => $fileName,
            'summary' => [
                'successful' => $successfulCount,
                'failed' => $failedCount,
            ],
            'success_rows' => $import->successRows,
            'failed_rows' => $import->failedRows,
        ], $status === 'failed' ? 422 : 200);
    }

    public function getBulkScores(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'term_id' => 'required|exists:terms,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        $assignedSubjectIds = ClassSubject::query()
            ->where('school_class_id', $validated['school_class_id'])
            ->where('session_id', $validated['session_id'])
            ->where('teacher_id', $request->user()->id)
            ->pluck('subject_id');

        if (!$request->user()->hasRole('super-admin') && $assignedSubjectIds->isEmpty() && !$this->teacherAccessService->isClassTeacherForClass(
            $request->user(),
            (int) $validated['school_class_id']
        )) {
            abort(403, 'You are not allowed to view scores for this class.');
        }

        if ($this->teacherAccessService->isClassTeacherForClass($request->user(), (int) $validated['school_class_id'])) {
            $scores = Score::where('school_class_id', $validated['school_class_id'])
                ->where('term_id', $validated['term_id'])
                ->where('session_id', $validated['session_id'])
                ->with('enrollment.student', 'subject', 'assessment')
                ->get();
        } else {
            $scores = Score::where('school_class_id', $validated['school_class_id'])
                ->where('term_id', $validated['term_id'])
                ->where('session_id', $validated['session_id'])
                ->whereIn('subject_id', $assignedSubjectIds)
                ->with('enrollment.student', 'subject', 'assessment')
                ->get();
        }

        return response()->json($scores);
    }
}
