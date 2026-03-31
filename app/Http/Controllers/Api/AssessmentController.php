<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Score;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /**
     * List assessments (with filters + pagination)
     */
    public function index(Request $request)
    {
        $query = Assessment::with(['term', 'session'])
            ->latest();

        // Optional filters
        if ($request->term_id) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->session_id) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $assessments = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $assessments
        ]);
    }

    /**
     * Create assessment
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:ca,exam'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'weight' => ['nullable', 'integer', 'min:1']
        ]);

        // 🔥 Prevent duplicates
        // $exists = Assessment::where([
        //     'name' => $data['name'],
        //     'term_id' => $data['term_id'],
        //     'session_id' => $data['session_id'],
        // ])->exists();

        // if ($exists) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Assessment already exists for this term & session'
        //     ], 422);
        // }

        $assessment = Assessment::create([
            ...$data,
            'weight' => $data['weight'] ?? 1
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Assessment created successfully',
            'data' => $assessment,
        ], 201);
    }

    /**
     * Show single assessment
     */
    public function show(Assessment $assessment)
    {
        return response()->json([
            'status' => 'success',
            'data' => $assessment->load('term', 'session')
        ]);
    }

    /**
     * Update assessment
     */
    public function update(Request $request, Assessment $assessment)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:ca,exam'],
            'term_id' => ['sometimes', 'exists:terms,id'],
            'session_id' => ['sometimes', 'exists:sessions,id'],
            'max_score' => ['sometimes', 'numeric', 'min:1'],
            'weight' => ['sometimes', 'integer', 'min:1']
        ]);

        // 🔥 Prevent duplicate on update
        if (isset($data['name'])) {
            $exists = Assessment::where('id', '!=', $assessment->id)
                ->where('name', $data['name'])
                ->where('term_id', $data['term_id'] ?? $assessment->term_id)
                ->where('session_id', $data['session_id'] ?? $assessment->session_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Duplicate assessment for same term/session'
                ], 422);
            }
        }

        $assessment->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Assessment updated',
            'data' => $assessment->fresh()->load('term', 'session')
        ]);
    }

    /**
     * Delete assessment
     */
    public function destroy(Assessment $assessment)
    {
        // 🔥 Prevent deleting if used
        $used = Score::where('assessment_id', $assessment->id)->exists();

        if ($used) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete assessment already used in scores'
            ], 422);
        }

        $assessment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Assessment deleted successfully'
        ]);
    }
}

