<?php

namespace App\Http\Requests\Settings;

use App\Access\AccessRegistry;
use Illuminate\Foundation\Http\FormRequest;

class EditOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false;
    }
}
