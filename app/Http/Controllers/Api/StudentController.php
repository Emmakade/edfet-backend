<?php

namespace App\Http\Controllers\Api;

use App\Services\StudentAccountLinkService;
use App\Exports\StudentsWithEnrollmentsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Requests\Student\StudentUpdateRequest;
use App\Imports\StudentsImport;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()
            ->with([
                'schoolClass',
                'enrollments.schoolClass',
                'scores',
            ])
            ->when($request->filled('class_id'), function ($q) use ($request) {
                $q->whereHas('enrollments', function ($sub) use ($request) {
                    $sub->where('school_class_id', $request->integer('class_id'));
                });
            })
            ->when($request->filled('session_id'), function ($q) use ($request) {
                $q->whereHas('enrollments', function ($sub) use ($request) {
                    $sub->where('session_id', $request->integer('session_id'));
                });
            })
            ->orderBy('surname')
            ->orderBy('first_name');

        $students = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $students,
            'count' => $students->count(),
        ]);
    }

    public function store(StudentStoreRequest $request)
    {
        $data = $this->normalizeStudentPayload($request->validated());

        $student = DB::transaction(function () use ($data) {
            $student = Student::create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'surname' => $data['surname'],
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'photo_url' => $data['photo_url'] ?? null,
                'number_in_class' => $data['number_in_class'] ?? null,
                'school_class_id' => $data['school_class_id'] ?? null,
                'login_email' => $data['login_email'] ?? null,
                'admission_number' => null,
            ]);

            $student->update([
                'admission_number' => $this->generateAdmissionNumber($student->id),
            ]);

            if (!empty($data['create_login_account']) && !empty($data['login_email'])) {
                app(StudentAccountLinkService::class)->linkOrCreateForStudent(
                    $student->fresh(),
                    $data['login_email'],
                    true,
                    $data['login_password'] ?? null
                );
            }

            return $student->fresh(['schoolClass', 'enrollments.schoolClass']);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Student created successfully',
            'data' => $student,
        ], 201);
    }

    public function show(Student $student)
    {
        $student->load([
            'user',
            'scores',
            'attendances',
            'enrollments.schoolClass',
            'schoolClass',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $student,
        ]);
    }

    public function update(StudentUpdateRequest $request, Student $student)
    {
        $data = $this->normalizeStudentPayload($request->validated());

        $updatedStudent = DB::transaction(function () use ($student, $data) {
            $student->update([
                'first_name' => $data['first_name'] ?? $student->first_name,
                'middle_name' => array_key_exists('middle_name', $data) ? $data['middle_name'] : $student->middle_name,
                'surname' => $data['surname'] ?? $student->surname,
                'gender' => array_key_exists('gender', $data) ? $data['gender'] : $student->gender,
                'date_of_birth' => array_key_exists('date_of_birth', $data) ? $data['date_of_birth'] : $student->date_of_birth,
                'phone_number' => array_key_exists('phone_number', $data) ? $data['phone_number'] : $student->phone_number,
                'photo_url' => array_key_exists('photo_url', $data) ? $data['photo_url'] : $student->photo_url,
                'number_in_class' => array_key_exists('number_in_class', $data) ? $data['number_in_class'] : $student->number_in_class,
                'school_class_id' => array_key_exists('school_class_id', $data) ? $data['school_class_id'] : $student->school_class_id,
                'login_email' => array_key_exists('login_email', $data) ? $data['login_email'] : $student->login_email,
            ]);

            if (!empty($data['school_class_id']) && !empty($data['session_id'])) {
                Enrollment::firstOrCreate([
                    'student_id' => $student->id,
                    'school_class_id' => $data['school_class_id'],
                    'session_id' => $data['session_id'],
                ]);
            }

            if (!empty($data['create_login_account']) && !empty($student->login_email)) {
                app(StudentAccountLinkService::class)->linkOrCreateForStudent(
                    $student->fresh(),
                    $student->login_email,
                    true,
                    $data['login_password'] ?? null
                );
            } elseif ($student->user) {
                app(StudentAccountLinkService::class)->syncLinkedStudentUser($student->fresh('user'));
            }

            return $student->fresh(['schoolClass', 'enrollments.schoolClass']);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => $updatedStudent,
        ]);
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Student deleted successfully',
        ]);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'exists:sessions,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],

            'students' => ['required', 'array', 'min:1'],
            'students.*.first_name' => ['required', 'string', 'max:255'],
            'students.*.middle_name' => ['nullable', 'string', 'max:255'],
            'students.*.surname' => ['required', 'string', 'max:255'],
            'students.*.gender' => ['nullable', 'in:male,female'],
            'students.*.date_of_birth' => ['nullable'],
            'students.*.phone_number' => ['nullable', 'string', 'max:255'],
            'students.*.number_in_class' => ['nullable', 'integer'],
            'students.*.photo_url' => ['nullable', 'string', 'max:255'],
        ]);

        $sessionId = (int) $data['session_id'];
        $classId = (int) $data['school_class_id'];

        $successRows = [];
        $failedRows = [];

        DB::beginTransaction();

        try {
            foreach ($data['students'] as $index => $studentData) {
                $rowNumber = $index + 1;

                try {
                    $normalized = $this->normalizeStudentPayload($studentData);

                    $student = $this->findExistingStudent(
                        $normalized['first_name'],
                        $normalized['surname'],
                        $normalized['date_of_birth'] ?? null
                    );

                    $studentCreated = false;

                    if (! $student) {
                        $student = Student::create([
                            'first_name' => $normalized['first_name'],
                            'middle_name' => $normalized['middle_name'] ?? null,
                            'surname' => $normalized['surname'],
                            'gender' => $normalized['gender'] ?? null,
                            'date_of_birth' => $normalized['date_of_birth'] ?? null,
                            'phone_number' => $normalized['phone_number'] ?? null,
                            'photo_url' => $normalized['photo_url'] ?? null,
                            'number_in_class' => $normalized['number_in_class'] ?? null,
                            'school_class_id' => $classId,
                            'admission_number' => null,
                        ]);

                        $student->update([
                            'admission_number' => $this->generateAdmissionNumber($student->id),
                        ]);

                        $studentCreated = true;
                    } else {
                        $student->update([
                            'middle_name' => $student->middle_name ?: ($normalized['middle_name'] ?? null),
                            'gender' => $student->gender ?: ($normalized['gender'] ?? null),
                            'date_of_birth' => $student->date_of_birth ?: ($normalized['date_of_birth'] ?? null),
                            'phone_number' => $normalized['phone_number'] ?? $student->phone_number,
                            'photo_url' => $normalized['photo_url'] ?? $student->photo_url,
                            'number_in_class' => $normalized['number_in_class'] ?? $student->number_in_class,
                            'school_class_id' => $classId,
                        ]);
                    }

                    $enrollment = Enrollment::firstOrCreate([
                        'student_id' => $student->id,
                        'school_class_id' => $classId,
                        'session_id' => $sessionId,
                    ]);

                    $successRows[] = [
                        'row' => $rowNumber,
                        'student_id' => $student->id,
                        'admission_number' => $student->admission_number,
                        'name' => trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . $student->surname),
                        'student_status' => $studentCreated ? 'created' : 'reused_existing_student',
                        'enrollment_status' => $enrollment->wasRecentlyCreated ? 'enrolled' : 'already_enrolled',
                    ];
                } catch (\Throwable $e) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                        'data' => $studentData,
                    ];
                }
            }

            DB::commit();

            $status = count($failedRows) === 0
                ? 'completed'
                : (count($successRows) === 0 ? 'failed' : 'partial_success');

            return response()->json([
                'status' => $status,
                'message' => match ($status) {
                    'completed' => 'Students imported successfully',
                    'partial_success' => 'Students imported with some failed rows',
                    default => 'No students were imported',
                },
                'summary' => [
                    'successful' => count($successRows),
                    'failed' => count($failedRows),
                ],
                'success_rows' => $successRows,
                'failed_rows' => $failedRows,
            ], $status === 'failed' ? 422 : 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'failed',
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $students = Student::query()
            ->with(['schoolClass', 'enrollments.schoolClass'])
            ->when($request->filled('class_id'), function ($query) use ($request) {
                $query->whereHas('enrollments', function ($q) use ($request) {
                    $q->where('school_class_id', $request->integer('class_id'));
                });
            })
            ->when($request->filled('session_id'), function ($query) use ($request) {
                $query->whereHas('enrollments', function ($q) use ($request) {
                    $q->where('session_id', $request->integer('session_id'));
                });
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $students,
            'exported_count' => $students->count(),
        ]);
    }

    public function exportStudentsWithEnrollments(Request $request)
    {
        $classId = $request->filled('class_id') ? $request->integer('class_id') : null;
        $sessionId = $request->filled('session_id') ? $request->integer('session_id') : null;

        return Excel::download(
            new StudentsWithEnrollmentsExport($classId, $sessionId),
            'students_with_enrollments_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    public function importStudent(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
            'session_id' => 'required|exists:sessions,id',
            'school_class_id' => 'required|exists:school_classes,id',
        ]);

        $import = new StudentsImport(
            $validated['session_id'],
            $validated['school_class_id']
        );

        Excel::import($import, $request->file('file'));

        $successfulCount = count($import->successRows);
        $failedCount = count($import->failedRows);

        $status = $failedCount === 0
            ? 'completed'
            : ($successfulCount === 0 ? 'failed' : 'partial_success');

        return response()->json([
            'status' => $status,
            'message' => match ($status) {
                'completed' => 'Students imported successfully',
                'partial_success' => 'Students imported with some failed rows',
                default => 'No students were imported',
            },
            'summary' => [
                'successful' => $successfulCount,
                'failed' => $failedCount,
            ],
            'success_rows' => $import->successRows,
            'failed_rows' => $import->failedRows,
        ], $status === 'failed' ? 422 : 200);
    }

    public function getByClass(Request $request, $classId)
    {
        $students = Student::query()
            ->with(['schoolClass', 'enrollments.schoolClass'])
            ->whereHas('enrollments', function ($q) use ($classId, $request) {
                $q->where('school_class_id', $classId);

                if ($request->filled('session_id')) {
                    $q->where('session_id', $request->integer('session_id'));
                }
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $students,
            'count' => $students->count(),
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
            ->with('subject')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $subjects,
            'count' => $subjects->count(),
        ]);
    }

    private function normalizeStudentPayload(array $data): array
    {
        $normalized = $data;

        if (array_key_exists('first_name', $normalized) && $normalized['first_name'] !== null) {
            $normalized['first_name'] = strtoupper(trim((string) $normalized['first_name']));
        }

        if (array_key_exists('middle_name', $normalized)) {
            $normalized['middle_name'] = $this->nullableUpper($normalized['middle_name']);
        }

        if (array_key_exists('surname', $normalized) && $normalized['surname'] !== null) {
            $normalized['surname'] = strtoupper(trim((string) $normalized['surname']));
        }

        if (array_key_exists('gender', $normalized) && $normalized['gender'] !== null) {
            $normalized['gender'] = strtolower(trim((string) $normalized['gender']));
        }

        if (array_key_exists('phone_number', $normalized)) {
            $normalized['phone_number'] = $this->nullableString($normalized['phone_number']);
        }

        if (array_key_exists('photo_url', $normalized)) {
            $normalized['photo_url'] = $this->nullableString($normalized['photo_url']);
        }

        if (array_key_exists('date_of_birth', $normalized)) {
            $normalized['date_of_birth'] = $this->parseDateInput($normalized['date_of_birth']);
        }
        
        if (array_key_exists('login_email', $normalized)) {
            $normalized['login_email'] = $this->nullableEmail($normalized['login_email']);
        }

        return $normalized;
    }

    private function findExistingStudent(string $firstName, string $surname, ?string $dateOfBirth = null): ?Student
    {
        return Student::query()
            ->where('first_name', $firstName)
            ->where('surname', $surname)
            ->when(
                $dateOfBirth,
                fn ($q) => $q->whereDate('date_of_birth', $dateOfBirth),
                fn ($q) => $q->whereNull('date_of_birth')
            )
            ->first();
    }

    private function generateAdmissionNumber(int $studentId): string
    {
        return 'GNP/' . now()->format('Y') . '/' . str_pad($studentId, 4, '0', STR_PAD_LEFT);
    }

    private function nullableUpper($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : strtoupper($value);
    }

    private function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableEmail($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(strtolower((string) $value));

        return $value === '' ? null : $value;
    }

    private function parseDateInput($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $baseDate = new \DateTime('1899-12-30');
            $baseDate->add(new \DateInterval('P' . intval($value) . 'D'));

            return $baseDate->format('Y-m-d');
        }

        $value = trim((string) $value);

        $formats = ['Y-m-d', 'd/m/Y', 'j/n/Y', 'd/n/Y', 'j/m/Y'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new \InvalidArgumentException("Invalid date format: {$value}");
    }
}