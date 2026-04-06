<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentAccountLinkService
{
    public function linkOrCreateForStudent(
        Student $student,
        ?string $loginEmail = null,
        bool $createIfMissing = true,
        ?string $plainPassword = null
    ): ?User {
        $normalizedEmail = $this->normalizeEmail($loginEmail ?? $student->login_email ?? null);

        if (! $normalizedEmail) {
            return null;
        }

        $existingUser = User::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($existingUser) {
            $this->assignStudentRoleIfMissing($existingUser);

            $student->update([
                'user_id' => $existingUser->id,
                'login_email' => $normalizedEmail,
            ]);

            return $existingUser;
        }

        if (! $createIfMissing) {
            return null;
        }

        $password = $plainPassword ?: $this->makeDefaultPassword($student);

        $user = User::create([
            'name' => $student->full_name,
            'email' => $normalizedEmail,
            'password' => Hash::make($password),
        ]);

        $this->assignStudentRoleIfMissing($user);

        $student->update([
            'user_id' => $user->id,
            'login_email' => $normalizedEmail,
        ]);

        return $user;
    }

    public function syncLinkedStudentUser(Student $student): void
    {
        if (! $student->user) {
            return;
        }

        $student->user->update([
            'name' => $student->full_name,
            'email' => $student->login_email ?: $student->user->email,
        ]);

        $this->assignStudentRoleIfMissing($student->user);
    }

    private function assignStudentRoleIfMissing(User $user): void
    {
        if (! $user->hasRole('student')) {
            $user->assignRole('student');
        }
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = trim(Str::lower($email));

        return $email === '' ? null : $email;
    }

    private function makeDefaultPassword(Student $student): string
    {
        return $student->admission_number ?: ('student' . $student->id);
    }
}