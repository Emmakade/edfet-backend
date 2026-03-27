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
            'student_id' => ['required','exists:students,id'],
            'subject_id' => ['required','exists:subjects,id'],
            'term_id' => ['required','exists:terms,id'],
            'school_class_id' => ['required','exists:school_classes,id'],
            'session_id' => ['required','exists:sessions,id'],
            'ca_score' => ['nullable','numeric','min:0','max:100'],
            'exam_score' => ['nullable','numeric','min:0','max:100'],
        ];
    }
}
