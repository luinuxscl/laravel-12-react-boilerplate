<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Rely on middleware (auth + role:admin)
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:64',
                Rule::unique('roles', 'name')
                    ->ignore($role?->id)
                    ->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
        ];
    }
}
