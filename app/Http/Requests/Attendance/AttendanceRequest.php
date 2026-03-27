<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // class-teacher or super-admin
        return $this->user()->hasAnyRole(['super-admin','class-teacher']);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required','exists:students,id'],
            'session_id' => ['required','exists:sessions,id'],
            'term_id' => ['required','exists:terms,id'],
            'times_school_opened' => ['required','integer','min:0'],
            'times_present' => ['required','integer','min:0','lte:times_school_opened']
        ];
    }
}
