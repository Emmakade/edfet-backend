<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Services\TeacherAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherWorkspaceController extends Controller
{
    public function __construct(protected TeacherAccessService $teacherAccessService)
    {
    }

    public function assignments(Request $request): JsonResponse
    {
        $this->authorize('view-teacher-workspace');

        $teacher = $request->user()->load('classTeacherClasses');
        $subjectAssignments = ClassSubject::query()
            ->with(['subject', 'schoolClass', 'session'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('session_id')
            ->orderBy('school_class_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'roles' => $teacher->getRoleNames()->values(),
                ],
                'class_teacher_of' => $teacher->classTeacherClasses,
                'subject_assignments' => $subjectAssignments,
            ],
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $this->authorize('view-teacher-workspace');

        $sessionId = $request->filled('session_id') ? $request->integer('session_id') : null;
        $students = $this->teacherAccessService
            ->assignedStudentsQuery($request->user(), $sessionId)
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $students,
            'count' => $students->count(),
        ]);
    }
}
