<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only authenticated users with a student profile can update
        return $this->user() && $this->user()->student;
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
        ];
    }
}