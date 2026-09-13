<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('usr_admin', 'email')->ignore($this->route('staff')?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'roles' => ['required', 'array', 'min:1', 'max:50'],
            'roles.*' => ['required', 'integer', 'distinct', 'exists:admin_roles,id'],
            'version' => [$this->route('staff') ? 'required' : 'nullable', 'integer', 'min:0'],
            'role' => ['prohibited'],
            'permissions' => ['prohibited'],
            'password' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        if (is_string($email)) {
            $this->merge(['email' => mb_strtolower(trim($email))]);
        }
    }
}
