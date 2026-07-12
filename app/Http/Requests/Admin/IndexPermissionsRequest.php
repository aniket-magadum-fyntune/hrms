<?php

namespace App\Http\Requests\Admin;

use App\Access\AccessRegistry;
use Illuminate\Foundation\Http\FormRequest;

class IndexPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false;
    }
}
