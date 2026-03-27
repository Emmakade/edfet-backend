<?php

namespace App\Services;

use App\Models\Score;
use App\Models\GradeBoundary;
use App\Models\ClassSummary;
use App\Models\StudentResult;

class ResultComputationService
{
    public function computeClassResult($classId, $termId, $sessionId)
    {
        $scores = Score::with(['student', 'subject'])
            ->where('school_class_id', $classId)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->get();

        if ($scores->isEmpty()) {
            return ['message' => 'No scores found'];
        }

        $grouped = $scores->groupBy('subject_id');

        foreach ($grouped as $subjectId => $subjectScores) {
            $totals = $subjectScores->pluck('total')->filter()->toArray();

            $classAvg = round(collect($totals)->avg(), 2);
            $classHigh = collect($totals)->max();
            $classLow = collect($totals)->min();

            // Rank students
            $ranked = $subjectScores->sortByDesc('total')->values();

            foreach ($ranked as $index => $score) {
                $position = $index + 1;
                $boundary = GradeBoundary::findByScore((int) round($score->total));

                $score->update([
                    'grade' => $boundary?->grade,
                    'remark' => $boundary?->remark,
                    'class_average' => $classAvg,
                    'class_highest' => $classHigh,
                    'class_lowest' => $classLow,
                    'subject_position' => $position,
                ]);
            }

            // Cache summary for this class + subject + term
            ClassSummary::updateOrCreate(
                [
                    'school_class_id' => $classId,
                    'term_id' => $termId,
                    'session_id' => $sessionId,
                    'subject_id' => $subjectId,
                ],
                [
                    'average' => $classAvg,
                    'highest' => $classHigh,
                    'lowest' => $classLow,
                ]
            );
        }

        return ['status' => 'ok', 'message' => 'Computation complete'];
    }

    public function computeOverallResults($classId, $termId, $sessionId)
    {
        // Get all scores for class
        $scores = Score::where('school_class_id', $classId)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->get();

        if ($scores->isEmpty()) {
            return ['message' => 'No scores found'];
        }

        // Group by student
        $grouped = $scores->groupBy('student_id');

        $results = [];

        foreach ($grouped as $studentId => $studentScores) {

            $total = $studentScores->sum('total');
            $count = $studentScores->count();
            $average = $count > 0 ? round($total / $count, 2) : 0;

            $results[] = [
                'student_id' => $studentId,
                'total' => $total,
                'average' => $average
            ];
        }

        // 🔥 SORT DESC (highest first)
        usort($results, fn($a, $b) => $b['total'] <=> $a['total']);

        // 🔥 ASSIGN POSITIONS (TIE-SAFE)
        $position = 1;
        $prevScore = null;
        $skip = 0;

        foreach ($results as $index => $res) {

            if ($prevScore !== null && $res['total'] == $prevScore) {
                $skip++;
            } else {
                $position = $index + 1;
                $position -= $skip;
            }

            StudentResult::updateOrCreate(
                [
                    'student_id' => $res['student_id'],
                    'school_class_id' => $classId,
                    'term_id' => $termId,
                    'session_id' => $sessionId,
                ],
                [
                    'total_score' => $res['total'],
                    'average_score' => $res['average'],
                    'overall_position' => $position,
                ]
            );

            $prevScore = $res['total'];
        }

        return ['status' => 'ok', 'message' => 'Overall results computed'];
    }
}
