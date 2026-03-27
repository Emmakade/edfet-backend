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
    Route::post('logout', [AuthController::class, 'logout']);

    // School config - only super-admin (enforced by FormRequest)
    Route::get('school', [SchoolConfigController::class, 'show']);
    Route::put('school', [SchoolConfigController::class, 'update']);

    // Classes - role-based: class-teacher & super-admin (middleware or request authorize)
    Route::apiResource('classes', ClassController::class)->parameters(['classes' => 'school_class']);

    // Subjects - super-admin only for create/update/delete
    Route::apiResource('subjects', SubjectController::class)->only(['index','store','update','destroy']);

    // Students - class teachers and super-admins
    Route::apiResource('students', StudentController::class);
    Route::get('students/class/{classId}', [StudentController::class, 'getByClass']);

    // Attendance
    Route::post('attendances', [AttendanceController::class, 'store']);
    Route::get('attendances/{student}/{session}/{term}', [AttendanceController::class, 'show']);
    Route::get('attendances/class/{classId}/{session}/{term}', [AttendanceController::class, 'getByClass']);

    // Scores
    Route::post('scores', [ScoreController::class, 'store']);
    Route::post('scores/bulk', [ScoreController::class, 'bulkUpdate']);
    Route::post('scores/recompute', [ScoreController::class, 'recomputeAll']);
    Route::get('scores/{score}', [ScoreController::class, 'show']);
    Route::delete('scores/{score}', [ScoreController::class, 'destroy']);
    Route::get('scores/student/{studentId}/{termId}/{sessionId}', [ScoreController::class, 'getByStudent']);
    Route::get('scores/class/{classId}/{subjectId}/{termId}/{sessionId}', [ScoreController::class, 'getByClassSubject']);

    // Sessions - academic sessions management
    Route::apiResource('sessions', 'App\Http\Controllers\Api\SessionController');

    // Terms - academic terms within sessions
    Route::apiResource('terms', 'App\Http\Controllers\Api\TermController');

    // Enrollments - student enrollments in classes
    Route::apiResource('enrollments', 'App\Http\Controllers\Api\EnrollmentController');

    // Exams - exam management
    Route::apiResource('exams', 'App\Http\Controllers\Api\ExamController');

    // Grade Boundaries - grading scale management
    Route::apiResource('grade-boundaries', 'App\Http\Controllers\Api\GradeBoundaryController');

    // Remarks - remark templates management
    Route::apiResource('remarks', 'App\Http\Controllers\Api\RemarkController');

    // Class Summaries - class performance summaries
    Route::get('class-summaries/{class_id}/{term_id}/{session_id}', 'App\Http\Controllers\Api\ClassSummaryController@show');
    Route::get('class-summaries', 'App\Http\Controllers\Api\ClassSummaryController@index');

    // Report Generation
    Route::get('report-card/{studentId}', [ReportCardController::class, 'generate']);
    Route::get('report-card/class/{classId}/{termId}/{sessionId}', [ReportCardController::class, 'generateClass']);
    //Route::get('report-card/class/{classId}/{termId}/{sessionId}', 'App\Http\Controllers\Api\ReportController@classReport');
    //Route::get('report-card/term/{termId}/{sessionId}', 'App\Http\Controllers\Api\ReportController@termReport');

    // Dashboard & Analytics
    Route::get('dashboard/school-stats', 'App\Http\Controllers\Api\DashboardController@schoolStats');
    Route::get('dashboard/class-performance/{classId}/{termId}/{sessionId}', 'App\Http\Controllers\Api\DashboardController@classPerformance');
    Route::get('dashboard/student-progress/{studentId}', 'App\Http\Controllers\Api\DashboardController@studentProgress');

    // Utility endpoints
    Route::get('current-session', 'App\Http\Controllers\Api\SessionController@current');
    Route::get('current-term', 'App\Http\Controllers\Api\TermController@current');
    Route::get('active-classes', [ClassController::class, 'active']);
    Route::get('subjects/class/{classId}', [SubjectController::class, 'getByClass']);

    //TODO: Additional endpoints 
    // - bulk student import/export, attendance analytics, e.t.c.
});
