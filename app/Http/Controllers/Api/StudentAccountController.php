<?php

namespace App\Http\Controllers\Api;

use App\Exports\StudentAccountCredentialsExport;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentAccountLinkService;
use App\Services\StudentCredentialService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentAccountController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $students = Student::query()
            ->with(['user', 'enrollments.schoolClass'])
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
            ->get()
            ->map(function (Student $student) {
                $latestEnrollment = $student->enrollments
                    ->sortByDesc('session_id')
                    ->first();

                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'login_email' => $student->login_email,
                    'user_id' => $student->user?->id,
                    'user_linked' => (bool) $student->user,
                    'class_name' => $latestEnrollment?->schoolClass?->name,
                    'session_id' => $latestEnrollment?->session_id,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $students,
            'count' => $students->count(),
        ]);
    }

    public function createMissingAccounts(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'email_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $emailDomain = $validated['email_domain'] ?? 'school.local';

        $query = Student::query()->with('user');

        if (! empty($validated['student_ids'])) {
            $query->whereIn('id', $validated['student_ids']);
        }

        $students = $query->get();

        $accountLinkService = app(StudentAccountLinkService::class);
        $credentialService = app(StudentCredentialService::class);

        $created = [];
        $skipped = [];

        foreach ($students as $student) {
            if ($student->user) {
                $skipped[] = [
                    'student_id' => $student->id,
                    'reason' => 'Account already linked',
                ];
                continue;
            }

            $email = $student->login_email ?: $credentialService->generateEmailIfMissing($student, $emailDomain);
            $password = $credentialService->makeDefaultPassword($student);

            $user = $accountLinkService->linkOrCreateForStudent(
                $student->fresh(),
                $email,
                true,
                $password
            );

            $created[] = [
                'student_id' => $student->id,
                'user_id' => $user?->id,
                'login_email' => $email,
                'password' => $password,
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Missing student accounts processed successfully',
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    public function resetPassword(Request $request, Student $student)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $result = app(StudentCredentialService::class)->resetPassword(
            $student->load('user'),
            $validated['password'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Student password reset successfully',
            'data' => $result,
        ]);
    }

    public function exportCredentials(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'session_id' => ['nullable', 'integer', 'exists:sessions,id'],
            'create_missing_accounts' => ['nullable', 'boolean'],
            'reset_passwords' => ['nullable', 'boolean'],
        ]);

        return Excel::download(
            new StudentAccountCredentialsExport(
                $validated['class_id'] ?? null,
                $validated['session_id'] ?? null,
                (bool) ($validated['create_missing_accounts'] ?? false),
                (bool) ($validated['reset_passwords'] ?? false)
            ),
            'student_account_credentials_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->hasAnyRole(['super-admin']),
            403,
            'Unauthorized'
        );
    }
}