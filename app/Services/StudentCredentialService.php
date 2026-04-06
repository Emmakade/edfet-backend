<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentCredentialService
{
    public function makeDefaultPassword(Student $student): string
    {
        return $student->admission_number ?: ('student' . $student->id);
    }

    public function resetPassword(Student $student, ?string $plainPassword = null): array
    {
        if (! $student->user) {
            throw new \RuntimeException('This student does not have a linked user account.');
        }

        $newPassword = $plainPassword ?: $this->makeDefaultPassword($student);

        $student->user->update([
            'password' => Hash::make($newPassword),
        ]);

        return [
            'student_id' => $student->id,
            'user_id' => $student->user->id,
            'login_email' => $student->login_email ?: $student->user->email,
            'new_password' => $newPassword,
        ];
    }

    public function generateEmailIfMissing(Student $student, string $domain = 'school.local'): string
    {
        if ($student->login_email) {
            return strtolower(trim($student->login_email));
        }

        $base = Str::slug($student->full_name ?: ('student-' . $student->id), '.');
        $base = str_replace('-', '.', $base);

        return strtolower($base . '.' . $student->id . '@' . $domain);
    }
}