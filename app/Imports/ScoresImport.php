<?php

namespace App\Imports;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\Subject;
use App\Services\ResultComputationService;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class ScoresImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    protected int $termId;
    protected int $sessionId;
    protected int $schoolClassId;

    public array $successRows = [];
    public array $failedRows = [];

    protected array $touchedSubjectResults = [];

    public function __construct($termId, $sessionId, $schoolClassId)
    {
        $this->termId = (int) $termId;
        $this->sessionId = (int) $sessionId;
        $this->schoolClassId = (int) $schoolClassId;
    }

    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $data = $row->toArray();

        try {
            $enrollmentId = $this->extractInteger($data, ['enrollment_id', 'enrollment id'], 'Valid enrollment_id is required');
            $subjectId = $this->extractInteger($data, ['subject_id', 'subject id'], 'Valid subject_id is required');
            $assessmentId = $this->extractInteger($data, ['assessment_id', 'assessment id'], 'Valid assessment_id is required');
            $scoreValue = $this->extractScore($data, ['score']);

            $enrollment = Enrollment::query()
                ->whereKey($enrollmentId)
                ->where('school_class_id', $this->schoolClassId)
                ->where('session_id', $this->sessionId)
                ->first();

            if (! $enrollment) {
                throw new \Exception('Enrollment not found or does not belong to the selected class/session');
            }

            if (! Subject::query()->whereKey($subjectId)->exists()) {
                throw new \Exception('Subject not found');
            }

            if (! Assessment::query()->whereKey($assessmentId)->exists()) {
                throw new \Exception('Assessment not found');
            }

            $subjectBelongsToClass = ClassSubject::query()
                ->where('school_class_id', $this->schoolClassId)
                ->where('session_id', $this->sessionId)
                ->where('subject_id', $subjectId)
                ->exists();

            if (! $subjectBelongsToClass) {
                throw new \Exception('Subject is not assigned to the selected class/session');
            }

            Score::updateOrCreate(
                [
                    'enrollment_id' => $enrollmentId,
                    'subject_id' => $subjectId,
                    'assessment_id' => $assessmentId,
                    'term_id' => $this->termId,
                    'session_id' => $this->sessionId,
                    'school_class_id' => $this->schoolClassId,
                ],
                [
                    'score' => $scoreValue,
                ]
            );

            $this->rememberTouchedSubjectResult($enrollmentId, $subjectId);

            $this->successRows[] = [
                'row' => $rowIndex,
                'enrollment_id' => $enrollmentId,
                'subject_id' => $subjectId,
                'assessment_id' => $assessmentId,
                'score' => $scoreValue,
                'message' => 'Imported successfully',
            ];
        } catch (\Throwable $e) {
            $this->failedRows[] = [
                'row' => $rowIndex,
                'error' => $e->getMessage(),
                'data' => $data,
            ];
        }
    }

    public function recomputeTouchedResults(): void
    {
        $service = app(ResultComputationService::class);

        foreach ($this->touchedSubjectResults as $item) {
            $service->computeSubjectResult(
                $item['enrollment_id'],
                $item['subject_id'],
                $this->termId,
                $this->sessionId
            );
        }
    }

    private function rememberTouchedSubjectResult(int $enrollmentId, int $subjectId): void
    {
        $key = $enrollmentId . ':' . $subjectId;

        $this->touchedSubjectResults[$key] = [
            'enrollment_id' => $enrollmentId,
            'subject_id' => $subjectId,
        ];
    }

    private function extractInteger(array $row, array $possibleKeys, string $message): int
    {
        $value = $this->getValue($row, $possibleKeys);
        $value = $this->normalizeValue($value);

        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw new \Exception($message);
        }

        return (int) $value;
    }

    private function extractScore(array $row, array $possibleKeys): int
    {
        $value = $this->getValue($row, $possibleKeys);
        $value = $this->normalizeValue($value);

        if ($value === null || $value === '' || ! is_numeric($value) || $value < 0) {
            throw new \Exception('Valid score (numeric >= 0) is required');
        }

        return (int) round((float) $value);
    }

    private function getValue(array $row, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            $normalizedKey = $this->normalizeHeader($key);

            foreach ($row as $header => $value) {
                if ($this->normalizeHeader((string) $header) === $normalizedKey) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $value = str_replace("\xEF\xBB\xBF", '', $value);

        return strtolower((string) preg_replace('/[\s_\-]+/', '', trim($value)));
    }

    private function normalizeValue($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = str_replace("\xC2\xA0", ' ', $value);

        return trim($value);
    }
}