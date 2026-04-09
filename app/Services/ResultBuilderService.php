<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\StudentResult;
use App\Models\SubjectResult;

class ResultBuilderService
{
    public function buildStudentReport(int $enrollmentId, int $termId): array
    {
        $enrollment = Enrollment::with([
            'student.user',
            'schoolClass.school',
        ])->findOrFail($enrollmentId);

        $assessments = Assessment::query()
            ->orderBy('id')
            ->get();

        $classSubjects = ClassSubject::query()
            ->with('subject')
            ->where('school_class_id', $enrollment->school_class_id)
            ->where('session_id', $enrollment->session_id)
            ->orderBy('subject_id')
            ->get();

        $scores = Score::query()
            ->with('assessment')
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->where('session_id', $enrollment->session_id)
            ->where('school_class_id', $enrollment->school_class_id)
            ->get()
            ->groupBy('subject_id');

        $subjectResults = SubjectResult::query()
            ->with('subject')
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->get()
            ->keyBy('subject_id');

        $subjects = $classSubjects->map(function ($classSubject) use ($scores, $assessments, $subjectResults) {
            $subjectId = (int) $classSubject->subject_id;
            $subjectScores = $scores->get($subjectId, collect());
            $subjectResult = $subjectResults->get($subjectId);

            $assessmentBreakdown = $assessments->map(function ($assessment) use ($subjectScores) {
                $score = $subjectScores
                    ->where('assessment_id', $assessment->id)
                    ->first();

                return (object) [
                    'name' => $assessment->name,
                    'type' => $assessment->type,
                    'score' => $score?->score ?? 0,
                    'max_score' => $assessment->max_score,
                ];
            });

            return (object) [
                'subject' => (object) [
                    'id' => $classSubject->subject->id,
                    'name' => $classSubject->subject->name,
                ],
                'total' => $subjectResult?->total ?? 0,
                'grade' => $subjectResult?->grade,
                'remark' => $subjectResult?->remark,
                'subject_position' => $subjectResult?->subject_position,
                'class_average' => $subjectResult?->class_average,
                'class_highest' => $subjectResult?->class_highest,
                'class_lowest' => $subjectResult?->class_lowest,
                'assessments' => $assessmentBreakdown,
            ];
        });

        $result = StudentResult::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->first();

        return [
            'student' => $enrollment->student,
            'enrollment' => $enrollment,
            'subjects' => $subjects,
            'result' => $result,
        ];
    }
}