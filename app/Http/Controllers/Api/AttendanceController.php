<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceRequest;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Services\TeacherAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(protected TeacherAccessService $teacherAccessService)
    {
    }

    public function store(AttendanceRequest $request)
    {
        $data = $request->validated();
        $enrollment = $this->activeEnrollmentQuery()
            ->where('student_id', $data['student_id'])
            ->where('session_id', $data['session_id'])
            ->first();

        if (! $enrollment) {
            return response()->json([
                'message' => 'Student does not have an active enrollment in the selected session.',
            ], 422);
        }

        if (! $this->teacherAccessService->canManageClassAttendance(
            $request->user(),
            (int) $enrollment->school_class_id
        )) {
            abort(403, 'You are not allowed to manage attendance for this class.');
        }

        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'session_id' => $data['session_id'],
                'term_id' => $data['term_id'],
            ],
            [
                'times_school_opened' => $data['times_school_opened'],
                'times_present' => $data['times_present'],
            ]
        );

        return response()->json($attendance);
    }

    public function storeClassAttendance(Request $request)
    {
        $this->authorize('manage-attendance');

        $payload = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'session_id' => ['required', 'exists:sessions,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'attendances.*.times_school_opened' => ['required', 'integer', 'min:0'],
            'attendances.*.times_present' => ['required', 'integer', 'min:0'],
        ]);

        if (! $this->teacherAccessService->canManageClassAttendance(
            $request->user(),
            (int) $payload['school_class_id']
        )) {
            abort(403, 'You are not allowed to manage attendance for this class.');
        }

        $enrollments = $this->activeEnrollmentQuery()
            ->where('school_class_id', $payload['school_class_id'])
            ->where('session_id', $payload['session_id'])
            ->with('student')
            ->get()
            ->keyBy('student_id');

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'school_class_id' => ['No active enrolled students were found for the selected class and session.'],
            ]);
        }

        $submittedStudentIds = collect($payload['attendances'])
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $enrolledStudentIds = $enrollments->keys()->map(fn ($id) => (int) $id)->values();

        $unknownStudentIds = $submittedStudentIds->diff($enrolledStudentIds)->values()->all();
        $missingStudentIds = $enrolledStudentIds->diff($submittedStudentIds)->values()->all();

        if ($unknownStudentIds !== [] || $missingStudentIds !== []) {
            $errors = [];

            if ($unknownStudentIds !== []) {
                $errors['attendances'] = [
                    'Some submitted students do not have an active enrollment in the selected class/session: ' . implode(', ', $unknownStudentIds),
                ];
            }

            if ($missingStudentIds !== []) {
                $errors['attendances'] = array_merge(
                    $errors['attendances'] ?? [],
                    ['Attendance must be submitted for every actively enrolled student. Missing student IDs: ' . implode(', ', $missingStudentIds)]
                );
            }

            throw ValidationException::withMessages($errors);
        }

        foreach ($payload['attendances'] as $index => $attendanceItem) {
            if ((int) $attendanceItem['times_present'] > (int) $attendanceItem['times_school_opened']) {
                throw ValidationException::withMessages([
                    "attendances.$index.times_present" => ['The times present field must be less than or equal to times school opened.'],
                ]);
            }
        }

        $savedAttendances = DB::transaction(function () use ($payload) {
            return collect($payload['attendances'])->map(function (array $attendanceItem) use ($payload) {
                return Attendance::updateOrCreate(
                    [
                        'student_id' => $attendanceItem['student_id'],
                        'session_id' => $payload['session_id'],
                        'term_id' => $payload['term_id'],
                    ],
                    [
                        'times_school_opened' => $attendanceItem['times_school_opened'],
                        'times_present' => $attendanceItem['times_present'],
                    ]
                );
            })->values();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Class attendance saved successfully.',
            'school_class_id' => (int) $payload['school_class_id'],
            'session_id' => (int) $payload['session_id'],
            'term_id' => (int) $payload['term_id'],
            'count' => $savedAttendances->count(),
            'data' => $savedAttendances,
        ]);
    }

    public function show(Request $request, $studentId, $sessionId, $termId)
    {
        $enrollment = $this->activeEnrollmentQuery()
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->firstOrFail();

        if (! $this->teacherAccessService->canManageClassAttendance(
            $request->user(),
            (int) $enrollment->school_class_id
        )) {
            abort(403, 'You are not allowed to view attendance for this class.');
        }

        $attendance = Attendance::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->firstOrFail();

        return response()->json($attendance);
    }

    public function analytics(Request $request)
    {
        $this->authorize('manage-attendance');

        $filters = $request->validate([
            'class_id' => ['nullable', 'exists:school_classes,id'],
            'session_id' => ['nullable', 'exists:sessions,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
        ]);

        if (! empty($filters['class_id']) && ! $this->teacherAccessService->canManageClassAttendance(
            $request->user(),
            (int) $filters['class_id']
        )) {
            abort(403, 'You are not allowed to view attendance analytics for this class.');
        }

        $query = Attendance::query()
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->when(isset($filters['class_id']), function ($builder) use ($filters) {
                $builder->where('students.school_class_id', $filters['class_id']);
            })
            ->when(isset($filters['session_id']), function ($builder) use ($filters) {
                $builder->where('attendances.session_id', $filters['session_id']);
            })
            ->when(isset($filters['term_id']), function ($builder) use ($filters) {
                $builder->where('attendances.term_id', $filters['term_id']);
            });

        $summary = (clone $query)
            ->selectRaw('COUNT(attendances.id) as records_count')
            ->selectRaw('COALESCE(SUM(attendances.times_school_opened), 0) as total_opened')
            ->selectRaw('COALESCE(SUM(attendances.times_present), 0) as total_present')
            ->first();

        $attendanceRate = (float) ($summary->total_opened > 0
            ? round(($summary->total_present / $summary->total_opened) * 100, 2)
            : 0);

        return response()->json([
            'filters' => $filters,
            'records_count' => (int) $summary->records_count,
            'total_opened' => (int) $summary->total_opened,
            'total_present' => (int) $summary->total_present,
            'attendance_rate' => $attendanceRate,
        ]);
    }

    public function getByClass(Request $request, $classId, $sessionId, $termId)
    {
        if (! $this->teacherAccessService->canManageClassAttendance(
            $request->user(),
            (int) $classId
        )) {
            abort(403, 'You are not allowed to view attendance for this class.');
        }

        $attendances = Attendance::query()
            ->with('student')
            ->where('session_id', $sessionId)
            ->where('term_id', $termId)
            ->whereHas('student.enrollments', function ($query) use ($classId, $sessionId) {
                $query->where('school_class_id', $classId)
                    ->where('session_id', $sessionId)
                    ->where('status', 'active')
                    ->whereNull('left_at');
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $attendances,
            'count' => $attendances->count(),
        ]);
    }

    private function activeEnrollmentQuery(): Builder
    {
        return Enrollment::query()
            ->where('status', 'active')
            ->whereNull('left_at');
    }
}
