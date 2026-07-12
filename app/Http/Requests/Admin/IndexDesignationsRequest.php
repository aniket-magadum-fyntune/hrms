<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexDesignationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('designations.view') ?? false;
    }
}
