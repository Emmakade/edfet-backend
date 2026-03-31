<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index()
    {
        return Enrollment::with(['student', 'schoolClass', 'session'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        // Prevent duplicate enrollment
        $exists = Enrollment::where([
            'student_id' => $request->student_id,
            'session_id' => $request->session_id,
        ])->exists();

        if ($exists) {
            return response()->json(['message' => 'Student already enrolled for this session'], 422);
        }

        return Enrollment::create($request->all());
    }

    public function show($id)
    {
        return Enrollment::with(['student', 'schoolClass', 'session'])->findOrFail($id);
    }

    public function destroy($id)
    {
        Enrollment::findOrFail($id)->delete();
        return response()->json(['message' => 'Enrollment removed']);
    }

    public function promote(Request $request)
    {
        $request->validate([
            'from_class_id' => 'required',
            'to_class_id' => 'required',
            'session_id' => 'required',
        ]);

        $students = Enrollment::where('school_class_id', $request->from_class_id)
            ->where('session_id', $request->session_id)
            ->get();

        foreach ($students as $enrollment) {
            Enrollment::create([
                'student_id' => $enrollment->student_id,
                'school_class_id' => $request->to_class_id,
                'session_id' => $request->session_id,
                'status' => 'promoted'
            ]);
        }

        return response()->json(['message' => 'Students promoted successfully']);
    }
}