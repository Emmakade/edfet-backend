<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super-admin','class-teacher']);
    }

    public function rules(): array
    {
        $studentId = $this->route('student') ? $this->route('student')->id : null;

        return [
            'first_name' => ['sometimes','required','string','max:255'],
            'last_name' => ['sometimes','nullable','string','max:255'],
            'gender' => ['sometimes','nullable','in:male,female,other'],
            'admission_number' => ['sometimes','required','string','max:50','unique:students,admission_number,'.$studentId],
            'school_class_id' => ['sometimes','nullable','exists:school_classes,id'],
            'date_of_birth' => ['sometimes','nullable','date'],
            'number_in_class' => ['sometimes','nullable','integer'],
            'photo_url' => ['sometimes','nullable','string']
        ];
    }
}
