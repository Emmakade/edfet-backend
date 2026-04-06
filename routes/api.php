<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SchoolConfigController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\ReportCardController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\TermController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\GradeBoundaryController;
use App\Http\Controllers\Api\RemarkController;
use App\Http\Controllers\Api\ClassSummaryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\StudentAccountController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Sanctum protected API routes and public auth routes.
|
*/

// Public auth routes
Route::post('register', [AuthController::class, 'register']); // restrict later in middleware if needed
Route::post('login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);

    // School config - only super-admin (enforced by FormRequest)
    Route::post('school', [SchoolConfigController::class, 'store']);
    Route::get('school', [SchoolConfigController::class, 'show']);
    Route::put('school', [SchoolConfigController::class, 'update']);

    // Classes - role-based: class-teacher & super-admin (middleware or request authorize)
    Route::apiResource('classes', ClassController::class)->parameters(['classes' => 'school_class']);

    // Subjects - super-admin only for create/update/delete
    Route::apiResource('subjects', SubjectController::class)->only(['index','store','update','destroy']);

    // Students - class teachers and super-admins
    Route::get('students/export-enrollments', [StudentController::class, 'exportStudentsWithEnrollments']);
    Route::apiResource('students', StudentController::class);
    Route::get('students/class/{classId}', [StudentController::class, 'getByClass']);
    Route::post('students/import-student', [StudentController::class, 'importStudent']);
    Route::post('students/import', [StudentController::class, 'import']);
    Route::get('students/{studentId}/subjects/{sessionId}', [ClassSubjectController::class, 'getStudentSubjects']);

    Route::get('student-accounts', [StudentAccountController::class, 'index']);
    Route::post('student-accounts/create-missing', [StudentAccountController::class, 'createMissingAccounts']);
    Route::post('student-accounts/{student}/reset-password', [StudentAccountController::class, 'resetPassword']);
    Route::get('student-accounts/export-credentials', [StudentAccountController::class, 'exportCredentials']);
    

    // Attendance
    Route::post('attendances', [AttendanceController::class, 'store']);
    Route::get('attendances/{student}/{session}/{term}', [AttendanceController::class, 'show']);
    Route::get('attendances/class/{classId}/{session}/{term}', [AttendanceController::class, 'getByClass']);

    // Scores
    Route::post('scores/import', [ScoreController::class, 'importScores'])->name('scores.import');
    Route::post('scores/bulk', [ScoreController::class, 'storeBulkScores']);
    Route::get('scores/{score}', [ScoreController::class, 'show'])->whereNumber('score');
    Route::delete('scores/{score}', [ScoreController::class, 'destroy'])->whereNumber('score');
    // Route::post('scores/recompute', [ScoreController::class, 'recomputeAll']);

    // Sessions - academic sessions management
    Route::apiResource('sessions', SessionController::class);

    // Terms - academic terms within sessions
    Route::apiResource('terms', TermController::class);

    // Enrollments - student enrollments in classes
    Route::apiResource('enrollments', EnrollmentController::class);
    Route::post('enrollments/promote', [EnrollmentController::class, 'promote']);

    // Assessments - exam management
    Route::apiResource('assessments', AssessmentController::class);

    // Grade Boundaries - grading scale management
    Route::apiResource('grade-boundaries', GradeBoundaryController::class);

    // Remarks - remark templates management
    Route::apiResource('remarks', RemarkController::class);

    // Class Summaries - class performance summaries
    Route::get('class-summaries/{class_id}/{term_id}/{session_id}', [ClassSummaryController::class, 'show']);
    Route::get('class-summaries', [ClassSummaryController::class, 'index']);

    // Report Generation
    Route::get('report-card', [ReportCardController::class, 'show']);
    Route::get('report-card/preview-pdf', [ReportCardController::class, 'previewPdf']);
    Route::get('report-card/download-pdf', [ReportCardController::class, 'downloadPdf']);

    Route::get('me/student-context', [ReportCardController::class, 'myContext']);
    Route::get('me/report-card', [ReportCardController::class, 'myReport']);
    Route::get('me/report-card/preview-pdf', [ReportCardController::class, 'myPreviewPdf']);
    Route::get('me/report-card/download-pdf', [ReportCardController::class, 'myDownloadPdf']);

    // Dashboard & Analytics
    Route::get('dashboard/school-stats', [DashboardController::class, 'schoolStats']);
    Route::get('dashboard/class-performance/{classId}/{termId}/{sessionId}', [DashboardController::class, 'classPerformance']);
    Route::get('dashboard/student-progress/{studentId}', [DashboardController::class, 'studentProgress']);

    // Utility endpoints
    Route::get('current-session', [SessionController::class, 'current']);
    Route::get('current-term', [TermController::class, 'current']);
    Route::get('active-classes', [ClassController::class, 'active']);
    Route::get('subjects/class/{classId}', [SubjectController::class, 'getByClass']);
    Route::post('class-subjects/assign', [ClassSubjectController::class, 'assign']);

    Route::post('students/import', [StudentController::class, 'import']);
    Route::get('students/export', [StudentController::class, 'export']);
    Route::get('attendance/analytics', [AttendanceController::class, 'analytics']);
});


