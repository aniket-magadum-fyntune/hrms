<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.view') ?? false;
    }
}
