<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Rely on middleware (auth + role:Admin)
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:64',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
        ];
    }
}
