<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_number' => sprintf('EMP-%05d', 90000 + (++self::$sequence)),
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'last_name' => fake()->lastName(),
            'suffix' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('09#########'),
            'address' => fake()->address(),
            'position' => fake()->randomElement(['Cashier', 'Clerk', 'Supervisor', 'Helper', 'Driver']),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'status' => EmployeeStatus::Active,
            'notes' => null,
        ];
    }

    /**
     * Linked to a new login account.
     */
    public function withAccount(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user !== null ? $user->id : User::factory(),
        ]);
    }

    public function resigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::Resigned,
        ]);
    }
}
