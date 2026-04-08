<?php

namespace App\Providers;

use App\Services\TeacherAccessService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Register model policies here later (optional)
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-school', function ($user) {
            return $user->hasRole('super-admin');
        });

        Gate::define('manage-teachers', function ($user) {
            return $user->hasRole('super-admin');
        });

        Gate::define('enter-scores', function ($user) {
            return $user->hasAnyRole(['super-admin', 'subject-teacher', 'class-teacher']);
        });

        Gate::define('manage-attendance', function ($user) {
            return $user->hasAnyRole(['super-admin', 'class-teacher']);
        });

        Gate::define('view-teacher-workspace', function ($user) {
            return $user->hasAnyRole(['super-admin', 'subject-teacher', 'class-teacher']);
        });

        Gate::define('manage-class-attendance', function ($user, int $classId) {
            return app(TeacherAccessService::class)->canManageClassAttendance($user, $classId);
        });

        Gate::define('enter-score-for-subject', function ($user, int $classId, int $subjectId, int $sessionId) {
            return app(TeacherAccessService::class)->canEnterScores($user, $classId, $subjectId, $sessionId);
        });
    }
}
