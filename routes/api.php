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
use App\Http\Controllers\Api\StudentManagementController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherWorkspaceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Sanctum protected API routes and public auth routes.
|
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::post('school', [SchoolConfigController::class, 'store']);
    Route::get('school', [SchoolConfigController::class, 'show']);
    Route::put('school', [SchoolConfigController::class, 'update']);

    Route::apiResource('classes', ClassController::class)->parameters(['classes' => 'school_class']);
    Route::apiResource('subjects', SubjectController::class)->only(['index', 'store', 'update', 'destroy']);

    // Student APIs
    Route::prefix('students')->group(function () {
        // Vue frontend friendly endpoints
        Route::get('list', [StudentManagementController::class, 'getStudents']);
        Route::post('create', [StudentManagementController::class, 'createStudent']);
        Route::post('import-file', [StudentManagementController::class, 'importStudent']);
        Route::post('promote', [StudentManagementController::class, 'promoteStudent']);
        Route::post('migrate', [StudentManagementController::class, 'migrateStudents']);

        // Existing REST and utility endpoints
        Route::get('export-enrollments', [StudentController::class, 'exportStudentsWithEnrollments']);
        Route::get('class/{classId}', [StudentController::class, 'getByClass']);
        Route::post('import', [StudentController::class, 'import']);
        Route::get('export', [StudentController::class, 'export']);
        Route::get('{studentId}/subjects/{sessionId}', [ClassSubjectController::class, 'getStudentSubjects']);
    });

    // Admin student profile management
    Route::get('students/{student}/profile', [StudentController::class, 'show']);
    Route::put('students/{student}/profile', [StudentController::class, 'update']);

    Route::apiResource('students', StudentController::class);

    //Students Account
    Route::get('student-accounts', [StudentAccountController::class, 'index']);
    Route::post('student-accounts/create-missing', [StudentAccountController::class, 'createMissingAccounts']);
    Route::post('student-accounts/{student}/reset-password', [StudentAccountController::class, 'resetPassword']);
    Route::get('student-accounts/export-credentials', [StudentAccountController::class, 'exportCredentials']);

    Route::apiResource('teachers', TeacherController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('teachers/{teacher}/class-teacher', [TeacherController::class, 'assignClassTeacher']);
    Route::delete('teachers/{teacher}/class-teacher/{schoolClass}', [TeacherController::class, 'removeClassTeacher'])->whereNumber('schoolClass');
    Route::post('teachers/{teacher}/subject-assignments', [TeacherController::class, 'assignSubjects']);
    Route::delete('teachers/{teacher}/subject-assignments', [TeacherController::class, 'unassignSubjects']);
    Route::get('teachers/{teacher}/assignments', [TeacherController::class, 'assignments']);
    Route::get('teacher/me/assignments', [TeacherWorkspaceController::class, 'assignments']);
    Route::get('teacher/me/students', [TeacherWorkspaceController::class, 'students']);

    Route::prefix('attendances')->group(function () {
        Route::post('', [AttendanceController::class, 'store']);
        Route::post('bulk', [AttendanceController::class, 'storeClassAttendance']);
        Route::get('class/{classId}/{session}/{term}', [AttendanceController::class, 'getByClass'])
            ->whereNumber('classId')
            ->whereNumber('session')
            ->whereNumber('term');
        Route::get('{student}/{session}/{term}', [AttendanceController::class, 'show'])
            ->whereNumber('student')
            ->whereNumber('session')
            ->whereNumber('term');
    });

    Route::prefix('teacher/me/attendances')->group(function () {
        Route::post('', [AttendanceController::class, 'store']);
        Route::post('bulk', [AttendanceController::class, 'storeClassAttendance']);
    });

    Route::post('scores/import', [ScoreController::class, 'importScores'])->name('scores.import');
    Route::post('scores/bulk', [ScoreController::class, 'storeBulkScores']);
    Route::post('teacher/me/scores/bulk', [ScoreController::class, 'storeBulkScores']);
    Route::get('scores/{score}', [ScoreController::class, 'show'])->whereNumber('score');
    Route::put('scores/{score}', [ScoreController::class, 'update'])->whereNumber('score');
    Route::delete('scores/{score}', [ScoreController::class, 'destroy'])->whereNumber('score');
    Route::get('teacher/me/scores/bulk', [ScoreController::class, 'getBulkScores']);
    Route::get('broadsheet', [ScoreController::class, 'broadsheet']);

    Route::apiResource('sessions', SessionController::class);
    Route::apiResource('terms', TermController::class);
    Route::apiResource('enrollments', EnrollmentController::class);
    Route::post('enrollments/promote', [EnrollmentController::class, 'promote']);
    Route::apiResource('assessments', AssessmentController::class);
    Route::apiResource('grade-boundaries', GradeBoundaryController::class);
    Route::apiResource('remarks', RemarkController::class);

    Route::get('class-summaries/{class_id}/{term_id}/{session_id}', [ClassSummaryController::class, 'show']);
    Route::get('class-summaries', [ClassSummaryController::class, 'index']);

    Route::get('report-card', [ReportCardController::class, 'show']);
    Route::get('report-card/preview-pdf', [ReportCardController::class, 'previewPdf']);
    Route::get('report-card/download-pdf', [ReportCardController::class, 'downloadPdf']);
    Route::get('report-card/download-class-pdf', [ReportCardController::class, 'downloadClassPdf']);

    Route::get('me/student-context', [ReportCardController::class, 'myContext']);
    Route::get('me/report-card', [ReportCardController::class, 'myReport']);
    Route::get('me/report-card/preview-pdf', [ReportCardController::class, 'myPreviewPdf']);
    Route::get('me/report-card/download-pdf', [ReportCardController::class, 'myDownloadPdf']);

    Route::get('dashboard/school-stats', [DashboardController::class, 'schoolStats']);
    Route::get('dashboard/class-performance/{classId}/{termId}/{sessionId}', [DashboardController::class, 'classPerformance']);
    Route::get('dashboard/student-progress/{studentId}', [DashboardController::class, 'studentProgress']);

    // Student Profile Management
    Route::get('me/profile', [StudentController::class, 'myProfile']);
    Route::put('me/profile', [StudentController::class, 'updateMyProfile']);

    Route::get('current-session', [SessionController::class, 'current']);
    Route::get('current-term', [TermController::class, 'current']);
    Route::get('active-classes', [ClassController::class, 'active']);
    Route::get('subjects/class/{classId}', [SubjectController::class, 'getByClass']);
    Route::post('class-subjects/assign', [ClassSubjectController::class, 'assign']);

    Route::get('attendance/analytics', [AttendanceController::class, 'analytics']);
});

