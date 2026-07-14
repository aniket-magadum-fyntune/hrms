<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => fake()->unique()->bothify('EMP-####'),
            'name' => fake()->name(),
            'work_email' => fake()->unique()->safeEmail(),
            'department_id' => null,
            'designation_id' => null,
            'manager_id' => null,
            'employment_status' => 'active',
            'joined_on' => fake()->dateTimeBetween('-5 years')->format('Y-m-d'),
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->afterCreating(function (Employee $employee) use ($user): void {
            $linkedUser = $user ?? User::factory()->create();

            $linkedUser->forceFill([
                'userable_type' => $employee->getMorphClass(),
                'userable_id' => $employee->id,
            ])->save();
        })->state(fn (array $attributes) => [
            'name' => $user instanceof User ? $user->name : $attributes['name'],
        ]);
    }

    public function withDepartment(?Department $department = null): static
    {
        return $this->state(fn () => [
            'department_id' => $department instanceof Department ? $department->id : Department::factory(),
        ]);
    }

    public function withDesignation(?Designation $designation = null): static
    {
        return $this->state(fn () => [
            'designation_id' => $designation instanceof Designation ? $designation->id : Designation::factory(),
        ]);
    }
}
