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
    
}
