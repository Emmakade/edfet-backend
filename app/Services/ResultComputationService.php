<?php

namespace App\Services;

use App\Models\Score;
use App\Models\GradeBoundary;
use App\Models\ClassSummary;
use App\Models\StudentResult;

class ResultComputationService
{
    public function computeOverallResults($classId, $termId, $sessionId)
    {
        $results = SubjectResult::where('term_id', $termId)
            ->whereHas('enrollment', function ($q) use ($classId, $sessionId) {
                $q->where('school_class_id', $classId)
                ->where('session_id', $sessionId);
            })
            ->get()
            ->groupBy('enrollment_id');

        $final = [];

        foreach ($results as $enrollmentId => $subjects) {

            $total = $subjects->sum('total');
            $avg = round($subjects->avg('total'), 2);

            $final[] = [
                'enrollment_id' => $enrollmentId,
                'total' => $total,
                'average' => $avg
            ];
        }

        usort($final, fn($a, $b) => $b['total'] <=> $a['total']);

        $position = 1;
        $prev = null;
        $skip = 0;

        foreach ($final as $index => $res) {

            if ($prev !== null && $res['total'] == $prev) {
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

            $prev = $res['total'];
        }
    }

    public function computeSubjectResult($enrollmentId, $subjectId, $termId)
    {
        $scores = Score::with('assessment')
            ->where('enrollment_id', $enrollmentId)
            ->where('subject_id', $subjectId)
            ->get();

        if ($scores->isEmpty()) return;

        $total = $scores->sum('score');

        $boundary = GradeBoundary::findByScore((int) round($total));

        SubjectResult::updateOrCreate(
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

    public function computeClassSubjectStats($classId, $termId, $sessionId)
    {
        $results = SubjectResult::where('term_id', $termId)
            ->whereHas('enrollment', function ($q) use ($classId, $sessionId) {
                $q->where('school_class_id', $classId)
                ->where('session_id', $sessionId);
            })
            ->get()
            ->groupBy('subject_id');

        foreach ($results as $subjectId => $group) {

            $totals = $group->pluck('total');

            $avg = round($totals->avg(), 2);
            $high = $totals->max();
            $low = $totals->min();

            $ranked = $group->sortByDesc('total')->values();

            $position = 1;
            $prev = null;
            $skip = 0;

            foreach ($ranked as $index => $res) {

                if ($prev !== null && $res->total == $prev) {
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

                $prev = $res->total;
            }
        }
    }
}