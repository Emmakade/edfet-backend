<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super-admin', 'class-teacher']);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'session_id' => ['nullable', 'exists:sessions,id'],
            'date_of_birth' => ['nullable'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'photo_url' => ['nullable', 'string', 'max:255'],
            'number_in_class' => ['nullable', 'integer'],
        ];
    }
}