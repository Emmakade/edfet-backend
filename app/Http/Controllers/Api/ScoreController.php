<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Score;
use App\Models\GradeBoundary;
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

    public function store(ScoreStoreRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {
            $score = Score::updateOrCreate(
                [
                    'student_id' => $data['student_id'],
                    'subject_id' => $data['subject_id'],
                    'school_class_id' => $data['school_class_id'],
                    'term_id' => $data['term_id'],
                    'session_id' => $data['session_id'],
                ],
                [
                    'ca_score' => $data['ca_score'] ?? 0,
                    'exam_score' => $data['exam_score'] ?? 0,
                ]
            );

            $total = ($score->ca_score ?? 0) + ($score->exam_score ?? 0);
            $boundary = GradeBoundary::findByScore((int) round($total));

            $score->update([
                'total' => $total,
                'grade' => $boundary?->grade,
                'remark' => $boundary?->remark,
            ]);

            // Recompute positions + cache
            $this->resultComputationService->computeClassResult(
                $data['school_class_id'], //changed from "class_id"
                $data['term_id'],
                $data['session_id']
            );

            $this->resultComputationService->computeOverallResults(
               $data['school_class_id'],
                $data['term_id'],
                $data['session_id']
            );

            return response()->json([
                'message' => 'Score saved successfully, and positions updated.',
                'score' => $score
            ]);
        });
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('enter-scores');

        $payload = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.student_id' => ['required', 'exists:students,id'],
            'scores.*.subject_id' => ['required', 'exists:subjects,id'],
            'scores.*.term_id' => ['required', 'exists:terms,id'],
            'scores.*.session_id' => ['required', 'exists:sessions,id'],
            'scores.*.school_class_id' => ['required', 'exists:school_classes,id'],
            'scores.*.ca_score' => ['nullable', 'numeric', 'min:0', 'max:40'],
            'scores.*.exam_score' => ['nullable', 'numeric', 'min:0', 'max:60'],
        ]);

        return DB::transaction(function () use ($payload) {

            foreach ($payload['scores'] as $s) {

                $ca = $s['ca_score'] ?? 0;
                $exam = $s['exam_score'] ?? 0;
                $total = $ca + $exam;

                $boundary = GradeBoundary::findByScore((int) round($total));

                Score::updateOrCreate(
                    [
                        'student_id' => $s['student_id'],
                        'subject_id' => $s['subject_id'],
                        'term_id' => $s['term_id'],
                        'session_id' => $s['session_id'],
                        'school_class_id' => $s['school_class_id'], 
                    ],
                    [
                        'ca_score' => $ca,
                        'exam_score' => $exam,
                        'total' => $total,
                        'grade' => $boundary?->grade,
                        'remark' => $boundary?->remark,
                    ]
                );
            }

            $first = $payload['scores'][0];

            $this->resultComputationService->computeClassResult(
                $first['school_class_id'], 
                $first['term_id'],
                $first['session_id']
            );

            $this->resultComputationService->computeOverallResults(
                $first['school_class_id'], 
                $first['term_id'],
                $first['session_id']
            );

            return response()->json([
                'message' => 'Bulk update completed and positions updated.',
                'updated_count' => count($payload['scores'])
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
