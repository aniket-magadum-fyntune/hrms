<?php

namespace App\Http\Requests\Admin;

use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_code')],
            'name' => ['required', 'string', 'max:255'],
            'work_email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique('employees', 'work_email')],
            'create_login' => ['boolean'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'designation_id' => ['nullable', 'integer', Rule::exists('designations', 'id')],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'employment_status' => ['required', 'string', Rule::in(Employee::statuses())],
            'joined_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateLoginEmail($validator);
                $this->validateDesignationCapacity($validator);
            },
        ];
    }

    private function validateLoginEmail(Validator $validator): void
    {
        if (! $this->boolean('create_login')) {
            return;
        }

        if (! $this->filled('work_email')) {
            $validator->errors()->add('work_email', 'The work email is required when creating login access.');

            return;
        }

        if (User::query()->where('email', $this->input('work_email'))->exists()) {
            $validator->errors()->add('work_email', 'A login account already exists for this email.');
        }
    }

    private function validateDesignationCapacity(Validator $validator): void
    {
        $designationId = $this->integer('designation_id');

        if ($designationId === 0) {
            return;
        }

        $designation = Designation::query()
            ->withCount('employees')
            ->find($designationId);

        if ($designation?->max_users !== null && $designation->employees_count >= $designation->max_users) {
            $validator->errors()->add('designation_id', "The {$designation->name} designation has reached its assignment limit.");
        }
    }
}
