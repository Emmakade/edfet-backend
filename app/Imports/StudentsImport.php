<?php

namespace App\Imports;

use App\Models\Enrollment;
use App\Models\Student;
use App\Services\StudentAccountLinkService;
use App\Services\StudentCredentialService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    protected int $sessionId;
    protected int $classId;
    protected bool $createLoginAccounts;
    protected string $emailDomain;

    public array $successRows = [];
    public array $failedRows = [];

    public function __construct(
        $sessionId,
        $classId,
        bool $createLoginAccounts = false,
        string $emailDomain = 'school.local'
    )
    {
        $this->sessionId = (int) $sessionId;
        $this->classId = (int) $classId;
        $this->createLoginAccounts = $createLoginAccounts;
        $this->emailDomain = trim(strtolower($emailDomain)) ?: 'school.local';
    }

    public function model(array $row)
    {
        $excelRowNumber = count($this->successRows) + count($this->failedRows) + 2;

        try {
            return DB::transaction(function () use ($row, $excelRowNumber) {
                $firstName = $this->normalizeName(
                    $this->getValue($row, ['first_name', 'first name', 'firstname'])
                );

                $middleName = $this->normalizeOptionalName(
                    $this->getValue($row, ['middle_name', 'middle name', 'middlename'])
                );

                $surname = $this->normalizeName(
                    $this->getValue($row, ['surname', 'last_name', 'last name', 'lastname'])
                );

                $gender = $this->normalizeGender(
                    $this->getValue($row, ['gender', 'sex'])
                );

                $dateOfBirth = $this->parseDate(
                    $this->getValue($row, ['date_of_birth', 'date of birth', 'dob', 'birth_date'])
                );

                $phoneNumber = $this->normalizeString(
                    $this->getValue($row, ['phone_number', 'phone number', 'phone'])
                );

                $numberInClass = $this->normalizeInteger(
                    $this->getValue($row, ['number_in_class', 'number in class', 'roll_no', 'roll number'])
                );

                $loginEmail = $this->normalizeEmail(
                    $this->getValue($row, ['login_email', 'login email', 'email'])
                );

                if (! $firstName) {
                    throw new \Exception('First name is required');
                }

                if (! $surname) {
                    throw new \Exception('Surname is required');
                }

                $student = Student::query()
                    ->where('first_name', $firstName)
                    ->where('surname', $surname)
                    ->when(
                        $dateOfBirth,
                        fn ($q) => $q->whereDate('date_of_birth', $dateOfBirth),
                        fn ($q) => $q->whereNull('date_of_birth')
                    )
                    ->first();

                $wasCreated = false;

                if (! $student) {
                    $student = Student::create([
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'surname' => $surname,
                        'gender' => $gender,
                        'date_of_birth' => $dateOfBirth,
                        'phone_number' => $phoneNumber,
                        'login_email' => $loginEmail,
                        'number_in_class' => $numberInClass,
                        'school_class_id' => $this->classId,
                    ]);

                    $student->update([
                        'admission_number' => $this->generateAdmissionNumber($student->id),
                    ]);

                    $wasCreated = true;
                } else {
                    $student->update([
                        'middle_name' => $student->middle_name ?: $middleName,
                        'gender' => $student->gender ?: $gender,
                        'date_of_birth' => $student->date_of_birth ?: $dateOfBirth,
                        'phone_number' => $phoneNumber ?: $student->phone_number,
                        'login_email' => $loginEmail ?: $student->login_email,
                        'number_in_class' => $numberInClass ?: $student->number_in_class,
                        'school_class_id' => $this->classId,
                    ]);
                }

                $enrollment = Enrollment::query()->firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'session_id' => $this->sessionId,
                        'school_class_id' => $this->classId,
                    ],
                    [
                        'status' => 'active',
                    ]
                );

                if ($this->createLoginAccounts && ! $student->login_email) {
                    $generatedEmail = app(StudentCredentialService::class)
                        ->generateEmailIfMissing($student, $this->emailDomain);

                    $student->update([
                        'login_email' => $generatedEmail,
                    ]);
                }

                $student->refresh();

                $linkedUser = null;
                if ($student->login_email) {
                    $linkedUser = app(StudentAccountLinkService::class)->linkOrCreateForStudent(
                        $student,
                        $student->login_email,
                        true
                    );
                }

                $this->successRows[] = [
                    'row' => $excelRowNumber,
                    'student_id' => $student->id,
                    'admission_number' => $student->admission_number,
                    'name' => trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . $student->surname),
                    'student_status' => $wasCreated ? 'created' : 'reused_existing_student',
                    'enrollment_status' => $enrollment->wasRecentlyCreated ? 'enrolled' : 'already_enrolled',
                    'login_email' => $student->login_email,
                    'user_linked' => (bool) $linkedUser,
                ];

                return $student;
            });
        } catch (\Throwable $e) {
            $this->failedRows[] = [
                'row' => $excelRowNumber,
                'error' => $e->getMessage(),
                'data' => $row,
            ];

            return null;
        }
    }

    private function generateAdmissionNumber(int $studentId): string
    {
        return 'GNP/' . now()->format('Y') . '/' . str_pad($studentId, 4, '0', STR_PAD_LEFT);
    }

    private function getValue(array $row, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            $normalizedKey = strtolower(str_replace([' ', '_'], '', $key));

            foreach (array_keys($row) as $header) {
                if (strtolower(str_replace([' ', '_'], '', $header)) === $normalizedKey) {
                    return $row[$header];
                }
            }
        }

        return null;
    }

    private function normalizeName($value): ?string
    {
        $value = $this->normalizeString($value);

        return $value ? strtoupper($value) : null;
    }

    private function normalizeOptionalName($value): ?string
    {
        $value = $this->normalizeString($value);

        return $value ? strtoupper($value) : null;
    }

    private function normalizeString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeEmail($value): ?string
    {
        $value = $this->normalizeString($value);

        return $value ? strtolower($value) : null;
    }

    private function normalizeInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new \Exception('Number in class must be numeric');
        }

        return (int) $value;
    }

    private function normalizeGender($value): ?string
    {
        $value = $this->normalizeString($value);

        if (! $value) {
            return null;
        }

        $value = strtolower($value);

        if (! in_array($value, ['male', 'female'], true)) {
            throw new \Exception('Gender must be male or female');
        }

        return $value;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return $this->convertExcelDateToPhpDate($value);
        }

        $value = trim((string) $value);

        $formats = ['d/m/Y', 'j/n/Y', 'd/n/Y', 'j/m/Y', 'Y-m-d'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new \Exception("Invalid date format: {$value}");
    }

    private function convertExcelDateToPhpDate($excelDate): ?string
    {
        try {
            $baseDate = new \DateTime('1899-12-30');
            $baseDate->add(new \DateInterval('P' . intval($excelDate) . 'D'));

            return $baseDate->format('Y-m-d');
        } catch (\Throwable $e) {
            throw new \Exception('Invalid Excel date format');
        }
    }
}
