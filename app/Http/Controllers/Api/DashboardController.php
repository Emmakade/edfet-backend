<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Score;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function schoolStats()
    {
        $stats = [
            'total_students' => Student::count(),
            'total_classes' => SchoolClass::count(),
            'total_subjects' => \App\Models\Subject::count(),
            'active_sessions' => \App\Models\SessionModel::where('active', true)->count(),
        ];

        return response()->json($stats);
    }

    public function classPerformance($classId, $termId, $sessionId)
    {
        $scores = Score::whereHas('student', function($q) use ($classId) {
            $q->where('school_class_id', $classId);
        })
        ->where('term_id', $termId)
        ->where('session_id', $sessionId)
        ->with('student', 'subject')
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