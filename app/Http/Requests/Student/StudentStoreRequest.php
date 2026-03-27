<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // class teachers and super-admins can create students
        return $this->user()->hasAnyRole(['super-admin','class-teacher']);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required','string','max:255'],
            'last_name' => ['nullable','string','max:255'],
            'gender' => ['nullable','in:male,female,other'],
            'admission_number' => ['required','string','max:50','unique:students,admission_number'],
            'school_class_id' => ['nullable','exists:school_classes,id'],
            'date_of_birth' => ['nullable','date'],
            'number_in_class' => ['nullable','integer'],
            'photo_url' => ['nullable','string']
        ];
    }
}
