<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['user', 'admin'])],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Pilihan role wajib diisi.',
            'role.in' => 'Role yang dipilih tidak valid (harus "user" atau "admin").',
        ];
    }
}
