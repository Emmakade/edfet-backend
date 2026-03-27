<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Requests\Student\StudentUpdateRequest;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('schoolClass','scores');

        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->input('class_id'));
        }

        $students = $query->get();
        return response()->json($students);
    }

    public function store(StudentStoreRequest $request)
    {
        $data = $request->validated();
        $student = Student::create($data);

        return response()->json($student, 201);
    }

    public function show(Student $student)
    {
        $student->load('scores','attendances','enrollments','schoolClass');
        return response()->json($student);
    }

    public function update(StudentUpdateRequest $request, Student $student)
    {
        $student->update($request->validated());
        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
