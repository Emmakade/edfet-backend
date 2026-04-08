<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TeacherAccessService
{
    public function canManageClassAttendance(User $user, int $classId): bool
    {
        return $user->hasRole('super-admin') || $this->isClassTeacherForClass($user, $classId);
    }

    public function isClassTeacherForClass(User $user, int $classId): bool
    {
        return $user->classTeacherClasses()->whereKey($classId)->exists();
    }

    public function canEnterScores(User $user, int $classId, int $subjectId, int $sessionId): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($this->isClassTeacherForClass($user, $classId)) {
            return true;
        }

        return ClassSubject::query()
            ->where('school_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session_id', $sessionId)
            ->where('teacher_id', $user->id)
            ->exists();
    }

    public function canViewScore(Score $score, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->canEnterScores(
            $user,
            (int) $score->school_class_id,
            (int) $score->subject_id,
            (int) $score->session_id
        );
    }

    public function assignedClassesQuery(User $user): Builder
    {
        return SchoolClass::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('class_teacher_id', $user->id)
                    ->orWhereHas('classSubjects', function (Builder $subjectQuery) use ($user) {
                        $subjectQuery->where('teacher_id', $user->id);
                    });
            });
    }

    public function assignedStudentsQuery(User $user, ?int $sessionId = null): Builder
    {
        $classIds = $this->assignedClassesQuery($user)->pluck('school_classes.id')->unique()->values();

        return Student::query()
            ->with(['schoolClass', 'enrollments.schoolClass'])
            ->whereHas('enrollments', function (Builder $query) use ($classIds, $sessionId) {
                $query->whereIn('school_class_id', $classIds)
                    ->when($sessionId, fn (Builder $q) => $q->where('session_id', $sessionId));
            });
    }

    public function studentEnrollmentForSession(int $studentId, int $sessionId): ?Enrollment
    {
        return Enrollment::query()
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->first();
    }
}
