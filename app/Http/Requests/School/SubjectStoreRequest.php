<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;

class SubjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super-admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'code' => ['nullable','string','max:20','unique:subjects,code']
        ];
    }
}
