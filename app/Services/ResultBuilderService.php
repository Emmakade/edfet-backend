<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\SubjectResult;
use App\Models\StudentResult;

class ResultBuilderService
{
    public function buildStudentReport($enrollmentId, $termId)
    {
        $enrollment = Enrollment::with([
            'student.user',
            'schoolClass'
        ])->findOrFail($enrollmentId);

        $student = $enrollment->student;

        // ✅ SUBJECT RESULTS (NOT scores)
        $subjects = SubjectResult::with('subject')
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->get();

        // ✅ OVERALL RESULT
        $result = StudentResult::where([
            'enrollment_id' => $enrollmentId,
            'term_id' => $termId
        ])->first();

        return [
            'student' => $student,
            'enrollment' => $enrollment,
            'subjects' => $subjects,
            'result' => $result,
        ];
    }
}