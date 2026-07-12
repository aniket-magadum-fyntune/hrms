<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DestroyRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.delete') ?? false;
    }
}
