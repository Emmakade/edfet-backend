<?php

namespace App\Exports;

use App\Models\Student;
use App\Services\StudentCredentialService;
use App\Services\StudentAccountLinkService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentAccountCredentialsExport implements FromCollection, WithHeadings
{
    protected ?int $classId;
    protected ?int $sessionId;
    protected bool $createMissingAccounts;
    protected bool $resetPasswords;

    public function __construct(
        ?int $classId = null,
        ?int $sessionId = null,
        bool $createMissingAccounts = false,
        bool $resetPasswords = false
    ) {
        $this->classId = $classId;
        $this->sessionId = $sessionId;
        $this->createMissingAccounts = $createMissingAccounts;
        $this->resetPasswords = $resetPasswords;
    }

    public function headings(): array
    {
        return [
            'student_id',
            'admission_number',
            'student_name',
            'class',
            'session_id',
            'login_email',
            'user_linked',
            'password',
        ];
    }

    public function collection(): Collection
    {
        $accountLinkService = app(StudentAccountLinkService::class);
        $credentialService = app(StudentCredentialService::class);

        $students = Student::query()
            ->with(['user', 'enrollments.schoolClass'])
            ->when($this->classId || $this->sessionId, function ($query) {
                $query->whereHas('enrollments', function ($q) {
                    if ($this->classId) {
                        $q->where('school_class_id', $this->classId);
                    }

                    if ($this->sessionId) {
                        $q->where('session_id', $this->sessionId);
                    }
                });
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        return $students->map(function (Student $student) use ($accountLinkService, $credentialService) {
            $password = null;

            if (! $student->login_email) {
                $student->update([
                    'login_email' => $credentialService->generateEmailIfMissing($student),
                ]);
                $student->refresh();
            }

            if ($this->createMissingAccounts && ! $student->user && $student->login_email) {
                $password = $credentialService->makeDefaultPassword($student);

                $accountLinkService->linkOrCreateForStudent(
                    $student->fresh(),
                    $student->login_email,
                    true,
                    $password
                );

                $student->refresh()->load('user', 'enrollments.schoolClass');
            }

            if ($this->resetPasswords && $student->user) {
                $resetResult = $credentialService->resetPassword($student->fresh('user'));
                $password = $resetResult['new_password'];
                $student->refresh()->load('user', 'enrollments.schoolClass');
            }

            $enrollment = $student->enrollments
                ->when($this->sessionId, fn ($c) => $c->where('session_id', $this->sessionId))
                ->sortByDesc('session_id')
                ->first();

            return [
                'student_id' => $student->id,
                'admission_number' => $student->admission_number,
                'student_name' => $student->full_name,
                'class' => $enrollment?->schoolClass?->name,
                'session_id' => $enrollment?->session_id,
                'login_email' => $student->login_email ?: $student->user?->email,
                'user_linked' => $student->user ? 'YES' : 'NO',
                'password' => $password,
            ];
        });
    }
}