<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Requests\Student\StudentUpdateRequest;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\Enrollment;

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

    public function import(Request $request)
    {
        // ✅ STEP 1: Validate request
        $data = $request->validate([
            'session_id' => ['required', 'exists:sessions,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],

            'students' => ['required', 'array', 'min:1'],
            'students.*.first_name' => ['required', 'string', 'max:255'],
            'students.*.last_name' => ['nullable', 'string', 'max:255'],
            'students.*.gender' => ['nullable', 'in:male,female,other'],
            'students.*.admission_number' => ['required', 'string', 'max:50'],
            'students.*.date_of_birth' => ['nullable', 'date'],
            'students.*.number_in_class' => ['nullable', 'integer'],
            'students.*.photo_url' => ['nullable', 'string'],
        ]);

        $sessionId = $data['session_id'];
        $classId = $data['school_class_id'];
        $timestamp = now();

        DB::beginTransaction();

        try {
            // ✅ STEP 2: Remove duplicate admission_numbers inside request
            $studentsPayload = collect($data['students'])
                ->unique('admission_number')
                ->values();

            // ✅ STEP 3: Prepare student data for upsert
            $studentRows = $studentsPayload->map(function ($student) use ($timestamp) {
                return [
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'] ?? null,
                    'gender' => $student['gender'] ?? null,
                    'admission_number' => $student['admission_number'],
                    'date_of_birth' => $student['date_of_birth'] ?? null,
                    'number_in_class' => $student['number_in_class'] ?? null,
                    'photo_url' => $student['photo_url'] ?? null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->toArray();

            // ✅ STEP 4: Insert or update students
            Student::upsert(
                $studentRows,
                ['admission_number'],
                ['first_name','last_name','gender','date_of_birth','number_in_class','photo_url','updated_at']
            );

            // ✅ STEP 5: Fetch students back (to get IDs)
            $students = Student::whereIn(
                'admission_number',
                $studentsPayload->pluck('admission_number')
            )->get()->keyBy('admission_number');

            // ✅ STEP 6: Prepare enrollments
            $enrollmentRows = [];

            foreach ($studentsPayload as $studentData) {
                $student = $students[$studentData['admission_number']];

                $enrollmentRows[] = [
                    'student_id' => $student->id,
                    'session_id' => $sessionId,
                    'school_class_id' => $classId,
                    'status' => 'active',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            // ✅ STEP 7: Upsert enrollments
            Enrollment::upsert(
                $enrollmentRows,
                ['student_id', 'session_id'], // unique constraint
                ['school_class_id', 'status', 'updated_at']
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Students imported and enrolled successfully',
                'students_count' => count($studentRows),
                'enrollments_count' => count($enrollmentRows),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $students = Student::query()
            ->with('schoolClass')
            ->when($request->filled('class_id'), function ($query) use ($request) {
                $query->where('school_class_id', $request->integer('class_id'));
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return response()->json([
            'data' => $students,
            'exported_count' => $students->count(),
        ]);
    }
}
