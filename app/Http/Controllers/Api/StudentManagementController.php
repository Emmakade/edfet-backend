<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Imports\StudentsImport;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\StudentAccountLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StudentManagementController extends Controller
{
    public function getStudents(Request $request)
    {
        $query = Student::query()
            ->with([
                'schoolClass',
                'enrollments.schoolClass',
                'currentEnrollment.session',
            ])
            ->when($request->filled('class_id'), function ($q) use ($request) {
                $q->whereHas('enrollments', function ($sub) use ($request) {
                    $sub->where('school_class_id', $request->integer('class_id'));

                    if ($request->filled('session_id')) {
                        $sub->where('session_id', $request->integer('session_id'));
                    }
                });
            })
            ->when($request->filled('session_id') && ! $request->filled('class_id'), function ($q) use ($request) {
                $q->whereHas('enrollments', function ($sub) use ($request) {
                    $sub->where('session_id', $request->integer('session_id'));
                });
            })
            ->orderBy('surname')
            ->orderBy('first_name');

        $students = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Students fetched successfully',
            'data' => $students,
            'count' => $students->count(),
        ]);
    }

    public function createStudent(StudentStoreRequest $request)
    {
        $data = $request->validated();

        $student = DB::transaction(function () use ($data) {
            $student = Student::create([
                'first_name' => strtoupper(trim($data['first_name'])),
                'middle_name' => $this->nullableUpper($data['middle_name'] ?? null),
                'surname' => strtoupper(trim($data['surname'])),
                'gender' => isset($data['gender']) ? strtolower(trim($data['gender'])) : null,
                'date_of_birth' => $this->parseDateInput($data['date_of_birth'] ?? null),
                'phone_number' => $this->nullableString($data['phone_number'] ?? null),
                'photo_url' => $this->nullableString($data['photo_url'] ?? null),
                'number_in_class' => $data['number_in_class'] ?? null,
                'school_class_id' => $data['school_class_id'] ?? null,
                'login_email' => isset($data['login_email']) ? strtolower(trim($data['login_email'])) : null,
                'admission_number' => null,
            ]);

            $student->update([
                'admission_number' => $this->generateAdmissionNumber($student->id),
            ]);

            if (! empty($data['school_class_id']) && ! empty($data['session_id'])) {
                Enrollment::firstOrCreate([
                    'student_id' => $student->id,
                    'school_class_id' => $data['school_class_id'],
                    'session_id' => $data['session_id'],
                ], [
                    'status' => 'active',
                ]);
            }

            if (! empty($data['create_login_account']) && ! empty($data['login_email'])) {
                app(StudentAccountLinkService::class)->linkOrCreateForStudent(
                    $student->fresh(),
                    $data['login_email'],
                    true,
                    $data['login_password'] ?? null
                );
            }

            return $student->fresh([
                'user',
                'schoolClass',
                'enrollments.schoolClass',
                'currentEnrollment.session',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Student created successfully',
            'data' => $student,
        ], 201);
    }

    public function importStudent(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv'],
            'session_id' => ['required', 'exists:sessions,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'create_login_accounts' => ['nullable', 'boolean'],
            'email_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $import = new StudentsImport(
            $validated['session_id'],
            $validated['school_class_id'],
            (bool) ($validated['create_login_accounts'] ?? false),
            $validated['email_domain'] ?? 'school.local'
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

    public function promoteStudent(Request $request)
    {
        $validated = $request->validate([
            'from_class_id' => ['required', 'exists:school_classes,id'],
            'to_class_id' => ['required', 'exists:school_classes,id', 'different:from_class_id'],
            'from_session_id' => ['nullable', 'exists:sessions,id'],
            'to_session_id' => ['required', 'exists:sessions,id'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $movedStudents = DB::transaction(function () use ($validated) {
            $sourceSessionId = $validated['from_session_id'] ?? $validated['to_session_id'];

            $enrollments = Enrollment::query()
                ->where('school_class_id', $validated['from_class_id'])
                ->where('session_id', $sourceSessionId)
                ->when(! empty($validated['student_ids']), function ($query) use ($validated) {
                    $query->whereIn('student_id', $validated['student_ids']);
                })
                ->get();

            foreach ($enrollments as $enrollment) {
                $enrollment->update(['status' => 'promoted']);

                Enrollment::firstOrCreate([
                    'student_id' => $enrollment->student_id,
                    'school_class_id' => $validated['to_class_id'],
                    'session_id' => $validated['to_session_id'],
                ], [
                    'status' => 'promoted',
                ]);

                Student::whereKey($enrollment->student_id)->update([
                    'school_class_id' => $validated['to_class_id'],
                ]);
            }

            return $enrollments->count();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Students promoted successfully',
            'moved_count' => $movedStudents,
        ]);
    }

    public function migrateStudents(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'to_class_id' => ['required', 'exists:school_classes,id'],
            'to_session_id' => ['required', 'exists:sessions,id'],
            'from_class_id' => ['nullable', 'exists:school_classes,id'],
            'from_session_id' => ['nullable', 'exists:sessions,id'],
            'status' => ['nullable', 'in:active,repeated,transferred,graduated,promoted'],
        ]);

        $status = $validated['status'] ?? 'transferred';

        DB::transaction(function () use ($validated, $status) {
            if (! empty($validated['from_class_id']) && ! empty($validated['from_session_id'])) {
                Enrollment::query()
                    ->whereIn('student_id', $validated['student_ids'])
                    ->where('school_class_id', $validated['from_class_id'])
                    ->where('session_id', $validated['from_session_id'])
                    ->update(['status' => $status]);
            }

            foreach ($validated['student_ids'] as $studentId) {
                Enrollment::updateOrCreate([
                    'student_id' => $studentId,
                    'school_class_id' => $validated['to_class_id'],
                    'session_id' => $validated['to_session_id'],
                ], [
                    'status' => $status,
                ]);

                Student::whereKey($studentId)->update([
                    'school_class_id' => $validated['to_class_id'],
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Students migrated successfully',
            'moved_count' => count($validated['student_ids']),
        ]);
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
