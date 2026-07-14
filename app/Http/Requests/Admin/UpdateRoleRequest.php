<?php

namespace App\Http\Requests\Admin;

use App\Access\AccessRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $roleName = $role instanceof Role ? $role->name : '';
        $roleId = $role instanceof Role ? $role->id : null;

        return [
            'name' => ['required', 'string', 'max:255', AccessRegistry::isSystemRole($roleName) ? Rule::in([$roleName]) : Rule::notIn(array_keys(AccessRegistry::roles())), Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($roleId)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }
}
