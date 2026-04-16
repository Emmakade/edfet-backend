<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super-admin', 'class-teacher']);
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'surname' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'in:male,female'],
            'school_class_id' => ['sometimes', 'nullable', 'exists:school_classes,id'],
            'session_id' => ['sometimes', 'nullable', 'exists:sessions,id'],
            'date_of_birth' => ['sometimes', 'nullable'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_in_class' => ['sometimes', 'nullable', 'integer'],

            'login_email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('students', 'login_email')->ignore($studentId),
            ],
            'admission_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('students', 'admission_number')->ignore($studentId),
            ],
            'create_login_account' => ['sometimes', 'nullable', 'boolean'],
            'login_password' => ['sometimes', 'nullable', 'string', 'min:6'],
        ];
    }
}