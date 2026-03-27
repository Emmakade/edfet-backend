<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;

class ClassStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // super-admin and class-teacher can create classes
        return $this->user()->hasAnyRole(['super-admin','class-teacher']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'level' => ['nullable','string','max:255'],
            'section' => ['nullable','string','max:10'],
            'school_id' => ['nullable','exists:schools,id'],
        ];
    }
}
