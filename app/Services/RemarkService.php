<?php

namespace App\Services;

use App\Models\Remark;
use App\Models\RemarkTemplate;
use App\Models\StudentResult;

class RemarkService
{
    public function generate($enrollmentId, $termId)
    {
        $result = StudentResult::where([
            'enrollment_id' => $enrollmentId,
            'term_id' => $termId
        ])->first();

        if (!$result) {
            return null;
        }

        $avg = $result->average_score;
        $position = $result->overall_position;

        return [
            'teacher' => $this->pickRemark('teacher', $avg, $position),
            'head' => $this->pickRemark('head', $avg, $position),
        ];
    }

    protected function pickRemark($type, $avg, $position)
    {
        $query = RemarkTemplate::where('type', $type)
            ->where('min_avg', '<=', $avg)
            ->where('max_avg', '>=', $avg);

        // Apply position filter if exists
        $query->where(function ($q) use ($position) {
            $q->whereNull('min_position')
              ->orWhere('min_position', '<=', $position);
        });

        $query->where(function ($q) use ($position) {
            $q->whereNull('max_position')
              ->orWhere('max_position', '>=', $position);
        });

        $remarks = $query->pluck('remark');

        if ($remarks->isEmpty()) {
            return "No remark available";
        }

        // 🔥 RANDOM SELECTION
        return $remarks->random();
    }

    public function store($enrollmentId, $termId)
    {
        $generated = $this->generate($enrollmentId, $termId);

        if (!$generated) return null;

        return Remark::updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'term_id' => $termId
            ],
            [
                'class_teacher_remark' => $generated['teacher'],
                'head_teacher_remark' => $generated['head']
            ]
        );
    }
}