<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Score;
use App\Models\GradeBoundary;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Score\ScoreStoreRequest;
use App\Services\ResultComputationService;

class ScoreController extends Controller
{
    protected $resultComputationService;

    public function __construct(ResultComputationService $resultComputationService)
    {
        $this->resultComputationService = $resultComputationService;
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

        return DB::transaction(function () use ($payload) {

            $saved = [];
            $failed = [];

            foreach ($payload['scores'] as $item) {

                try {
                    $score = Score::updateOrCreate(
                        [
                            'enrollment_id' => $item['enrollment_id'],
                            'subject_id' => $item['subject_id'],
                            'assessment_id' => $item['assessment_id'],
                        ],
                        [
                            'score' => $item['score'],
                        ]
                    );

                    // 🔥 Compute subject result immediately
                    app(ResultComputationService::class)
                        ->computeSubjectResult(
                            $item['enrollment_id'],
                            $item['subject_id'],
                            $payload['term_id']
                        );

                    $saved[] = $score;

                } catch (\Throwable $e) {
                    $failed[] = [
                        'data' => $item,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // 🔥 Compute once (NOT inside loop)
            $this->resultComputationService->computeClassSubjectStats(
                $payload['school_class_id'],
                $payload['term_id'],
                $payload['session_id']
            );

            $this->resultComputationService->computeOverallResults(
                $payload['school_class_id'],
                $payload['term_id'],
                $payload['session_id']
            );

            return response()->json([
                'message' => 'Bulk score upload processed',
                'saved_count' => count($saved),
                'failed_count' => count($failed),
                'failures' => $failed
            ]);
        });
    }


    public function show(Score $score)
    {
        return response()->json($score->load('student','subject'));
    }

    public function destroy(Score $score)
    {
        $this->authorize('enter-scores');
        $score->delete();
        return response()->json(['message'=>'Deleted']);
    }

    public function recomputeAll(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'term_id' => 'required|exists:terms,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        $this->resultComputationService->computeClassResult(
            $validated['school_class_id'],
            $validated['term_id'],
            $validated['session_id']
        );

        $this->resultComputationService->computeOverallResults(
            $validated['school_class_id'],
            $validated['term_id'],
            $validated['session_id']
        );

        return response()->json(['message' => 'All positions recomputed successfully.']);
    }
}
