<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassSubject;
use App\Models\Enrollment;

class ClassSubjectController extends Controller
{
    public function assign(Request $request)
{
    $data = $request->validate([
        'school_class_id' => ['required', 'exists:school_classes,id'],
        'session_id' => ['required', 'exists:sessions,id'],
        'subjects' => ['required', 'array'],
        'subjects.*.subject_id' => ['required', 'exists:subjects,id'],
        'subjects.*.teacher_id' => ['nullable', 'exists:users,id'],
    ]);

    $rows = collect($data['subjects'])->map(function ($subject) use ($data) {
        return [
            'school_class_id' => $data['school_class_id'],
            'session_id' => $data['session_id'],
            'subject_id' => $subject['subject_id'],
            'teacher_id' => $subject['teacher_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    })->toArray();

    ClassSubject::upsert(
        $rows,
        ['school_class_id', 'subject_id', 'session_id'],
        ['teacher_id', 'updated_at']
    );

    return response()->json([
        'message' => 'Subjects assigned to class successfully'
    ]);
}

}
