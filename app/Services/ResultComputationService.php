<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\GradeBoundary;
use App\Models\Score;
use App\Models\StudentResult;
use App\Models\SubjectResult;

class ResultComputationService
{
    public function recomputeClassResults(int $classId, int $termId, int $sessionId): void
    {
        $enrollments = Enrollment::query()
            ->where('school_class_id', $classId)
            ->where('session_id', $sessionId)
            ->get();

        $subjectIds = ClassSubject::query()
            ->where('school_class_id', $classId)
            ->where('session_id', $sessionId)
            ->pluck('subject_id');

        foreach ($enrollments as $enrollment) {
            foreach ($subjectIds as $subjectId) {
                $this->computeSubjectResult(
                    $enrollment->id,
                    (int) $subjectId,
                    $termId,
                    $sessionId
                );
            }
        }

        $this->computeClassSubjectStats($classId, $termId, $sessionId);
        $this->computeOverallResults($classId, $termId, $sessionId);
    }

    public function computeSubjectResult(
        int $enrollmentId,
        int $subjectId,
        int $termId,
        ?int $sessionId = null
    ): ?SubjectResult {
        $enrollment = Enrollment::query()->find($enrollmentId);

        if (! $enrollment) {
            return null;
        }

        $resolvedSessionId = $sessionId ?? (int) $enrollment->session_id;

        $subjectBelongsToClass = ClassSubject::query()
            ->where('school_class_id', $enrollment->school_class_id)
            ->where('session_id', $resolvedSessionId)
            ->where('subject_id', $subjectId)
            ->exists();

        if (! $subjectBelongsToClass) {
            SubjectResult::query()
                ->where('enrollment_id', $enrollmentId)
                ->where('subject_id', $subjectId)
                ->where('term_id', $termId)
                ->delete();

            return null;
        }

        $scores = Score::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('subject_id', $subjectId)
            ->where('term_id', $termId)
            ->where('session_id', $resolvedSessionId)
            ->where('school_class_id', $enrollment->school_class_id)
            ->get();

        $total = (int) $scores->sum('score');

        $boundary = GradeBoundary::findByScore((int) round($total));

        return SubjectResult::updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'subject_id' => $subjectId,
                'term_id' => $termId,
            ],
            [
                'total' => $total,
                'grade' => $boundary?->grade,
                'remark' => $boundary?->remark,
            ]
        );
    }

    public function removeUnassignedSubjectResults(int $enrollmentId, int $termId, int $sessionId): void
    {
        $enrollment = Enrollment::query()->find($enrollmentId);

        if (! $enrollment) {
            return;
        }

        $assignedSubjectIds = ClassSubject::query()
            ->where('school_class_id', $enrollment->school_class_id)
            ->where('session_id', $sessionId)
            ->pluck('subject_id');

        $query = SubjectResult::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId);

        if ($assignedSubjectIds->isNotEmpty()) {
            $query->whereNotIn('subject_id', $assignedSubjectIds);
        }

        $query->delete();
    }

    public function computeOverallResults(int $classId, int $termId, int $sessionId): void
    {
        $assignedSubjectIds = ClassSubject::query()
            ->where('school_class_id', $classId)
            ->where('session_id', $sessionId)
            ->pluck('subject_id');

        if ($assignedSubjectIds->isEmpty()) {
            return;
        }

        $results = SubjectResult::query()
            ->where('term_id', $termId)
            ->whereIn('subject_id', $assignedSubjectIds)
            ->whereHas('enrollment', function ($q) use ($classId, $sessionId) {
                $q->where('school_class_id', $classId)
                    ->where('session_id', $sessionId);
            })
            ->get()
            ->groupBy('enrollment_id');

        $final = [];

        foreach ($results as $enrollmentId => $subjects) {
            $total = (int) $subjects->sum('total');
            $average = $subjects->count() > 0
                ? round($subjects->avg('total'), 2)
                : 0;

            $final[] = [
                'enrollment_id' => (int) $enrollmentId,
                'total' => $total,
                'average' => $average,
            ];
        }

        usort($final, fn ($a, $b) => $b['total'] <=> $a['total']);

        $position = 1;
        $prevTotal = null;
        $skip = 0;

        foreach ($final as $index => $res) {
            if ($prevTotal !== null && $res['total'] === $prevTotal) {
                $skip++;
            } else {
                $position = $index + 1 - $skip;
            }

            StudentResult::updateOrCreate(
                [
                    'enrollment_id' => $res['enrollment_id'],
                    'term_id' => $termId,
                ],
                [
                    'total_score' => $res['total'],
                    'average_score' => $res['average'],
                    'overall_position' => $position,
                ]
            );

            $prevTotal = $res['total'];
        }
    }

    public function computeClassSubjectStats(int $classId, int $termId, int $sessionId): void
    {
        $assignedSubjectIds = ClassSubject::query()
            ->where('school_class_id', $classId)
            ->where('session_id', $sessionId)
            ->pluck('subject_id');

        if ($assignedSubjectIds->isEmpty()) {
            return;
        }

        $results = SubjectResult::query()
            ->where('term_id', $termId)
            ->whereIn('subject_id', $assignedSubjectIds)
            ->whereHas('enrollment', function ($q) use ($classId, $sessionId) {
                $q->where('school_class_id', $classId)
                    ->where('session_id', $sessionId);
            })
            ->get()
            ->groupBy('subject_id');

        foreach ($results as $subjectId => $group) {
            $totals = $group->pluck('total');

            $avg = round($totals->avg(), 2);
            $high = (int) $totals->max();
            $low = (int) $totals->min();

            $ranked = $group->sortByDesc('total')->values();

            $position = 1;
            $prev = null;
            $skip = 0;

            foreach ($ranked as $index => $res) {
                if ($prev !== null && (int) $res->total === (int) $prev) {
                    $skip++;
                } else {
                    $position = $index + 1 - $skip;
                }

                $res->update([
                    'subject_position' => $position,
                    'class_average' => $avg,
                    'class_highest' => $high,
                    'class_lowest' => $low,
                ]);

                $prev = (int) $res->total;
            }
        }
    }
}