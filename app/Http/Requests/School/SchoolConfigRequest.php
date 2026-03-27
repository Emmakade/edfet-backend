<?php

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;

class SchoolConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        // only super-admin can manage school config (routes already protected)
        return $this->user()->hasRole('super-admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'address' => ['nullable','string'],
            'mailbox' => ['nullable','string'],
            'phone' => ['nullable','string'],
            'motto' => ['nullable','string'],
            'next_term_begins' => ['nullable','date'],
            'extra' => ['nullable','array']
        ];
    }
}
