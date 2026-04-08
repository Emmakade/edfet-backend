<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GradeBoundary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradeBoundaryController extends Controller
{
    public function index()
    {
        $gradeBoundaries = GradeBoundary::query()
            ->orderByDesc('min_score')
            ->orderByDesc('priority')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $gradeBoundaries,
            'count' => $gradeBoundaries->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $this->ensureNoOverlap($data);

        $gradeBoundary = GradeBoundary::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Grade boundary created successfully',
            'data' => $gradeBoundary,
        ], 201);
    }

    public function show(GradeBoundary $gradeBoundary)
    {
        return response()->json([
            'status' => 'success',
            'data' => $gradeBoundary,
        ]);
    }

    public function update(Request $request, GradeBoundary $gradeBoundary)
    {
        $data = $this->validatePayload($request, $gradeBoundary);
        $this->ensureNoOverlap($data, $gradeBoundary);

        $gradeBoundary->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Grade boundary updated successfully',
            'data' => $gradeBoundary->fresh(),
        ]);
    }

    public function destroy(GradeBoundary $gradeBoundary)
    {
        $gradeBoundary->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Grade boundary deleted successfully',
        ]);
    }

    private function validatePayload(Request $request, ?GradeBoundary $gradeBoundary = null): array
    {
        $gradeBoundaryId = $gradeBoundary?->id;

        $data = $request->validate([
            'min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_score' => ['required', 'integer', 'min:0', 'max:100'],
            'grade' => [
                'required',
                'string',
                'max:20',
                Rule::unique('grade_boundaries', 'grade')->ignore($gradeBoundaryId),
            ],
            'remark' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ]);

        if ((int) $data['min_score'] > (int) $data['max_score']) {
            throw ValidationException::withMessages([
                'min_score' => ['The min score must be less than or equal to the max score.'],
            ]);
        }

        $data['grade'] = strtoupper(trim($data['grade']));
        $data['remark'] = array_key_exists('remark', $data) && $data['remark'] !== null
            ? trim($data['remark'])
            : null;
        $data['priority'] = $data['priority'] ?? (int) $data['max_score'];

        return $data;
    }

    private function ensureNoOverlap(array $data, ?GradeBoundary $gradeBoundary = null): void
    {
        $overlapExists = GradeBoundary::query()
            ->when($gradeBoundary, fn ($query) => $query->whereKeyNot($gradeBoundary->id))
            ->where('min_score', '<=', $data['max_score'])
            ->where('max_score', '>=', $data['min_score'])
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'min_score' => ['This score range overlaps with an existing grade boundary.'],
            ]);
        }
    }
}
