<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\TeacherClassAssignmentRequest;
use App\Http\Requests\Teacher\TeacherStoreRequest;
use App\Http\Requests\Teacher\TeacherSubjectAssignmentRequest;
use App\Http\Requests\Teacher\TeacherUpdateRequest;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TeacherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-teachers');

        $teachers = User::query()
            ->role(['class-teacher', 'subject-teacher'])
            ->with($this->teacherRelations())
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->role($request->string('role')->toString());
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $teachers->map(fn (User $teacher) => $this->transformTeacher($teacher)),
            'count' => $teachers->count(),
        ]);
    }

    public function store(TeacherStoreRequest $request): JsonResponse
    {
        $teacher = DB::transaction(function () use ($request) {
            $teacher = User::create([
                'name' => trim($request->string('name')->toString()),
                'email' => strtolower($request->string('email')->toString()),
                'phone' => $request->input('phone'),
                'password' => Hash::make($request->string('password')->toString()),
            ]);

            $teacher->syncRoles($request->input('roles', []));

            return $teacher->fresh($this->teacherRelations());
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Teacher created successfully.',
            'data' => $this->transformTeacher($teacher),
        ], 201);
    }

    public function show(User $teacher): JsonResponse
    {
        $this->authorize('manage-teachers');
        $this->ensureTeacher($teacher);

        $teacher->load($this->teacherRelations());

        return response()->json([
            'status' => 'success',
            'data' => $this->transformTeacher($teacher),
        ]);
    }

    public function update(TeacherUpdateRequest $request, User $teacher): JsonResponse
    {
        $this->ensureTeacher($teacher);

        $teacher = DB::transaction(function () use ($request, $teacher) {
            $payload = $request->safe()->except(['password', 'roles']);

            if (array_key_exists('name', $payload)) {
                $payload['name'] = trim((string) $payload['name']);
            }

            if (array_key_exists('email', $payload)) {
                $payload['email'] = strtolower((string) $payload['email']);
            }

            if ($request->filled('password')) {
                $payload['password'] = Hash::make($request->string('password')->toString());
            }

            $teacher->update($payload);

            if ($request->has('roles')) {
                $roles = $request->input('roles', []);
                $this->assertRoleChangeDoesNotBreakAssignments($teacher, $roles);
                $teacher->syncRoles($roles);
            }

            return $teacher->fresh($this->teacherRelations());
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Teacher updated successfully.',
            'data' => $this->transformTeacher($teacher),
        ]);
    }

    public function destroy(User $teacher): JsonResponse
    {
        $this->authorize('manage-teachers');
        $this->ensureTeacher($teacher);

        DB::transaction(function () use ($teacher) {
            SchoolClass::query()
                ->where('class_teacher_id', $teacher->id)
                ->update(['class_teacher_id' => null]);

            ClassSubject::query()
                ->where('teacher_id', $teacher->id)
                ->update(['teacher_id' => null]);

            $teacher->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Teacher deleted successfully.',
        ]);
    }

    public function assignClassTeacher(TeacherClassAssignmentRequest $request, User $teacher): JsonResponse
    {
        $this->ensureTeacher($teacher);

        if (! $teacher->hasRole('class-teacher')) {
            $teacher->assignRole('class-teacher');
        }

        $schoolClass = SchoolClass::query()
            ->with('classTeacher')
            ->findOrFail($request->integer('school_class_id'));

        $schoolClass->update(['class_teacher_id' => $teacher->id]);
        $schoolClass->load('classTeacher');

        return response()->json([
            'status' => 'success',
            'message' => 'Class teacher assigned successfully.',
            'data' => $schoolClass,
        ]);
    }

    public function removeClassTeacher(User $teacher, SchoolClass $schoolClass): JsonResponse
    {
        $this->authorize('manage-teachers');
        $this->ensureTeacher($teacher);

        if ((int) $schoolClass->class_teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'school_class_id' => ['This class is not assigned to the selected teacher as class teacher.'],
            ]);
        }

        $schoolClass->update(['class_teacher_id' => null]);

        return response()->json([
            'status' => 'success',
            'message' => 'Class teacher assignment removed successfully.',
        ]);
    }

    public function assignSubjects(TeacherSubjectAssignmentRequest $request, User $teacher): JsonResponse
    {
        $this->ensureTeacher($teacher);

        if (! $teacher->hasRole('subject-teacher')) {
            $teacher->assignRole('subject-teacher');
        }

        $subjectIds = collect($request->input('subject_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existingSubjectIds = ClassSubject::query()
            ->where('school_class_id', $request->integer('school_class_id'))
            ->where('session_id', $request->integer('session_id'))
            ->whereIn('subject_id', $subjectIds)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $missingSubjectIds = $subjectIds->diff($existingSubjectIds)->values()->all();

        if ($missingSubjectIds !== []) {
            throw ValidationException::withMessages([
                'subject_ids' => ['Some selected subjects are not assigned to the selected class/session: ' . implode(', ', $missingSubjectIds)],
            ]);
        }

        DB::transaction(function () use ($request, $teacher, $subjectIds) {
            if ($request->boolean('sync')) {
                ClassSubject::query()
                    ->where('school_class_id', $request->integer('school_class_id'))
                    ->where('session_id', $request->integer('session_id'))
                    ->where('teacher_id', $teacher->id)
                    ->whereNotIn('subject_id', $subjectIds)
                    ->update(['teacher_id' => null]);
            }

            ClassSubject::query()
                ->where('school_class_id', $request->integer('school_class_id'))
                ->where('session_id', $request->integer('session_id'))
                ->whereIn('subject_id', $subjectIds)
                ->update(['teacher_id' => $teacher->id]);
        });

        $assignments = $this->teacherAssignmentsForClassSession(
            $teacher,
            $request->integer('school_class_id'),
            $request->integer('session_id')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Subject assignments saved successfully.',
            'data' => $assignments,
            'count' => $assignments->count(),
        ]);
    }

    public function unassignSubjects(TeacherSubjectAssignmentRequest $request, User $teacher): JsonResponse
    {
        $this->authorize('manage-teachers');
        $this->ensureTeacher($teacher);

        $subjectIds = collect($request->input('subject_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        ClassSubject::query()
            ->where('school_class_id', $request->integer('school_class_id'))
            ->where('session_id', $request->integer('session_id'))
            ->where('teacher_id', $teacher->id)
            ->whereIn('subject_id', $subjectIds)
            ->update(['teacher_id' => null]);

        $assignments = $this->teacherAssignmentsForClassSession(
            $teacher,
            $request->integer('school_class_id'),
            $request->integer('session_id')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Subject assignments removed successfully.',
            'data' => $assignments,
            'count' => $assignments->count(),
        ]);
    }

    public function assignments(User $teacher): JsonResponse
    {
        $this->authorize('manage-teachers');
        $this->ensureTeacher($teacher);

        $teacher->load($this->teacherRelations());

        return response()->json([
            'status' => 'success',
            'data' => [
                'class_teacher_of' => $teacher->classTeacherClasses,
                'subject_assignments' => $teacher->subjectAssignments,
            ],
        ]);
    }

    private function ensureTeacher(User $teacher): void
    {
        abort_unless(
            $teacher->hasAnyRole(['class-teacher', 'subject-teacher', 'super-admin']),
            404,
            'Teacher not found.'
        );
    }

    private function assertRoleChangeDoesNotBreakAssignments(User $teacher, array $roles): void
    {
        if (! in_array('class-teacher', $roles, true) && $teacher->classTeacherClasses()->exists()) {
            throw ValidationException::withMessages([
                'roles' => ['This teacher is still assigned as a class teacher. Remove the class assignment before removing the class-teacher role.'],
            ]);
        }

        if (! in_array('subject-teacher', $roles, true) && $teacher->subjectAssignments()->whereNotNull('teacher_id')->exists()) {
            throw ValidationException::withMessages([
                'roles' => ['This teacher still has subject assignments. Remove the subject assignments before removing the subject-teacher role.'],
            ]);
        }
    }

    private function teacherRelations(): array
    {
        return [
            'roles',
            'classTeacherClasses',
            'subjectAssignments.subject',
            'subjectAssignments.schoolClass',
            'subjectAssignments.session',
        ];
    }

    private function teacherAssignmentsForClassSession(User $teacher, int $classId, int $sessionId)
    {
        return ClassSubject::query()
            ->with(['subject', 'schoolClass', 'session', 'teacher'])
            ->where('teacher_id', $teacher->id)
            ->where('school_class_id', $classId)
            ->where('session_id', $sessionId)
            ->orderBy('subject_id')
            ->get();
    }

    private function transformTeacher(User $teacher): array
    {
        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'roles' => $teacher->getRoleNames()->values(),
            'class_teacher_of' => $teacher->classTeacherClasses,
            'subject_assignments' => $teacher->subjectAssignments,
            'created_at' => $teacher->created_at,
            'updated_at' => $teacher->updated_at,
        ];
    }
}
