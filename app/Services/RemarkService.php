<?php

namespace App\Services;

use App\Models\Remark;
use App\Models\StudentResult;

class RemarkService
{
    public function upsertManualRemark(
        int $enrollmentId,
        int $termId,
        ?string $teacherRemark = null,
        ?string $principalRemark = null
    ): Remark {
        return Remark::updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'term_id' => $termId,
            ],
            [
                'teacher_remark' => $this->normalizeNullableText($teacherRemark),
                'principal_remark' => $this->normalizeNullableText($principalRemark),
            ]
        );
    }

    public function upsertAutoRemark(int $enrollmentId, int $termId): Remark
    {
        $studentResult = StudentResult::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->first();

        $average = $studentResult?->average_score ?? 0;

        return Remark::updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'term_id' => $termId,
            ],
            [
                'teacher_remark' => $this->generateTeacherRemark((float) $average),
                'principal_remark' => $this->generatePrincipalRemark((float) $average),
            ]
        );
    }

    public function getOrGenerateRemark(int $enrollmentId, int $termId): Remark
    {
        $existing = Remark::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->upsertAutoRemark($enrollmentId, $termId);
    }

    public function generateTeacherRemark(float $average): string
    {
        return match (true) {
            $average >= 80 => 'An excellent performance. Keep it up.',
            $average >= 70 => 'Very good performance. Remain focused and consistent.',
            $average >= 60 => 'Good performance. There is still room for improvement.',
            $average >= 50 => 'Fair performance. More effort is needed next term.',
            $average >= 40 => 'A weak performance. Must work much harder.',
            default => 'A poor performance. Urgent improvement is required.',
        };
    }

    public function generatePrincipalRemark(float $average): string
    {
        return match (true) {
            $average >= 80 => 'Outstanding result. We are proud of your achievement.',
            $average >= 70 => 'Commendable performance. Keep aiming higher.',
            $average >= 60 => 'A satisfactory result. Greater effort will yield better outcomes.',
            $average >= 50 => 'This result is fair, but you can do much better.',
            $average >= 40 => 'This performance is below expectation. Improvement is necessary.',
            default => 'This result is not acceptable. Serious academic improvement is required.',
        };
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}