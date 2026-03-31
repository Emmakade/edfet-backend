<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(AttendanceRequest $request)
    {
        $data = $request->validated();

        // update or create attendance row for the student/term/session
        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'session_id' => $data['session_id'],
                'term_id' => $data['term_id']
            ],
            [
                'times_school_opened' => $data['times_school_opened'],
                'times_present' => $data['times_present']
            ]
        );

        return response()->json($attendance);
    }

    public function show($studentId, $sessionId, $termId)
    {
        $attendance = Attendance::where('student_id',$studentId)
            ->where('session_id',$sessionId)
            ->where('term_id',$termId)
            ->firstOrFail();

        return response()->json($attendance);
    }

    public function analytics(Request $request)
    {
        $filters = $request->validate([
            'class_id' => ['nullable', 'exists:school_classes,id'],
            'session_id' => ['nullable', 'exists:sessions,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
        ]);

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
}
