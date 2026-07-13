<?php

namespace App\Http\Requests\Admin;

use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'employee_code' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_code')->ignore($employee?->id)],
            'name' => ['required', 'string', 'max:255'],
            'work_email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique('employees', 'work_email')->ignore($employee?->id)],
            'create_login' => ['boolean'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'designation_id' => ['nullable', 'integer', Rule::exists('designations', 'id')],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'employment_status' => ['required', 'string', Rule::in(Employee::statuses())],
            'joined_on' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateLoginEmail($validator);
                $this->validateManager($validator);
                $this->validateDesignationCapacity($validator);
            },
        ];
    }

    private function validateLoginEmail(Validator $validator): void
    {
        $employee = $this->route('employee');

        if (! $employee instanceof Employee || ! $this->boolean('create_login') || $employee->user !== null) {
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

    private function validateManager(Validator $validator): void
    {
        $employee = $this->route('employee');

        if ($employee instanceof Employee && $this->integer('manager_id') === $employee->id) {
            $validator->errors()->add('manager_id', 'An employee cannot report to themselves.');
        }
    }

    private function validateDesignationCapacity(Validator $validator): void
    {
        $employee = $this->route('employee');
        $designationId = $this->integer('designation_id');

        if (! $employee instanceof Employee || $designationId === 0 || $employee->designation_id === $designationId) {
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
