<?php

namespace App\Http\Requests\Admin;

use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('designations.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $designation = $this->route('designation');
        $designationId = $designation instanceof Designation ? $designation->id : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')->ignore($designationId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_users' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
