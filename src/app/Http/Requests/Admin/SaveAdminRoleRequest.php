<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('admin_roles', 'name')->ignore($this->route('role')?->id), Rule::notIn(['super_admin', 'Superadmin', 'Super Admin'])],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['present', 'array', 'max:100'],
            'permissions.*' => ['string', 'distinct', Rule::in(AdminAccess::permissions())],
            'version' => [$this->route('role') ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['permissions' => $this->input('permissions', [])]);
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $permissions = $this->input('permissions', []);
            foreach ($permissions as $permission) {
                $module = explode('.', $permission)[0];
                if (! in_array($module.'.view', $permissions, true)) {
                    $validator->errors()->add('permissions', 'Enable Show Menu / View before allowing an action.');
                }
            }
        }];
    }
}
