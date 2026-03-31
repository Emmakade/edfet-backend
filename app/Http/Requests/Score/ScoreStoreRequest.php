<?php

namespace App\Http\Requests\Score;

use Illuminate\Foundation\Http\FormRequest;

class ScoreStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // subject-teacher, class-teacher and super-admin can enter scores
        return $this->user()->hasAnyRole(['super-admin','subject-teacher','class-teacher']);
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => ['required','exists:enrollments,id'],
            'subject_id' => ['required','exists:subjects,id'],
            'term_id' => ['required','exists:terms,id'],
            'assessment_id' => ['required','exists:assessments,id'],
            'school_class_id' => ['required','exists:school_classes,id'],
            'session_id' => ['required','exists:sessions,id'],
            'score' => ['nullable','numeric','min:0','max:100'],
        ];
    }
}
