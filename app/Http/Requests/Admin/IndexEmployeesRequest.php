<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.view') ?? false;
    }
}
