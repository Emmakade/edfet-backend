<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\SessionModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;

class DashboardController extends Controller
{
    public function schoolStats()
    {
        $stats = [
            'total_students' => Student::count(),
            'total_classes' => SchoolClass::count(),
            'total_teachers' => User::role(['class-teacher', 'subject-teacher'])->count(),
            'total_subjects' => Subject::count(),
            'active_sessions' => SessionModel::where('active', true)->count(),
        ];

        return response()->json($stats);
    }

    public function classPerformance($classId, $termId, $sessionId)
    {
        $scores = Score::query()
            ->where('scores.school_class_id', $classId)
            ->where('scores.term_id', $termId)
            ->where('scores.session_id', $sessionId)
            ->with(['student', 'subject'])
            ->get();

        $performance = $scores->groupBy('subject.name')->map(function($subjectScores) {
            return [
                'subject' => $subjectScores->first()->subject->name,
                'average' => $subjectScores->avg('score'),
                'highest' => $subjectScores->max('score'),
                'lowest' => $subjectScores->min('score'),
                'count' => $subjectScores->count(),
            ];
        });

        return response()->json($performance);
    }

    public function studentProgress($studentId)
    {
        $student = Student::with('scores.subject', 'scores.term', 'scores.session')->findOrFail($studentId);

        $progress = $student->scores->groupBy(['session.name', 'term.name'])->map(function($termScores) {
            return $termScores->groupBy('subject.name')->map(function($subjectScores) {
                return [
                    'subject' => $subjectScores->first()->subject->name,
                    'average' => $subjectScores->avg('score'),
                    'scores' => $subjectScores->pluck('score'),
                ];
            });
        });

        return response()->json([
            'student' => $student,
            'progress' => $progress,
        ]);
    }
}
