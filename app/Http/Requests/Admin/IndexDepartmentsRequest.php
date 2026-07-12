<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexDepartmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('departments.view') ?? false;
    }
}
