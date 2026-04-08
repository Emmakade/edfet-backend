<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassSubjectController extends Controller
{
    public function assign(Request $request)
    {
        $this->authorize('manage-teachers');

        $data = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'session_id' => ['required', 'exists:sessions,id'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.subject_id' => ['required', 'exists:subjects,id'],
            'subjects.*.teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        $teacherIds = collect($data['subjects'])
            ->pluck('teacher_id')
            ->filter()
            ->unique()
            ->values();

        if ($teacherIds->isNotEmpty()) {
            $invalidTeacherIds = User::query()
                ->whereIn('id', $teacherIds)
                ->get()
                ->reject(fn (User $teacher) => $teacher->hasAnyRole(['super-admin', 'subject-teacher']))
                ->pluck('id')
                ->values();

            if ($invalidTeacherIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'subjects' => ['Every assigned subject teacher must have the subject-teacher role.'],
                ]);
            }
        }

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

        $assignments = ClassSubject::query()
            ->with(['subject', 'teacher', 'schoolClass', 'session'])
            ->where('school_class_id', $data['school_class_id'])
            ->where('session_id', $data['session_id'])
            ->orderBy('subject_id')
            ->get();

        return response()->json([
            'message' => 'Subjects assigned to class successfully',
            'data' => $assignments,
        ]);
    }

    public function getStudentSubjects($studentId, $sessionId)
    {
        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $subjects = ClassSubject::query()
            ->where('school_class_id', $enrollment->school_class_id)
            ->where('session_id', $sessionId)
            ->with(['subject', 'teacher'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $subjects,
            'count' => $subjects->count(),
        ]);
    }
}
