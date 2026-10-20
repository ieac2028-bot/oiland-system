<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Customer->value,
            'branch_id' => null,
            'phone' => fake()->numerify('+9715########'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SuperAdmin->value,
            'branch_id' => null,
        ]);
    }

    public function branchAdmin(?Branch $branch = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::BranchAdmin->value,
            'branch_id' => $branch?->id ?? Branch::factory(),
        ]);
    }

    public function branchStaff(?Branch $branch = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::BranchStaff->value,
            'branch_id' => $branch?->id ?? Branch::factory(),
        ]);
    }

    public function hrOfficer(?Branch $branch = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::HrOfficer->value,
            'branch_id' => $branch?->id ?? Branch::factory(),
        ]);
    }

    public function employee(?Branch $branch = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Employee->value,
            'branch_id' => $branch?->id ?? Branch::factory(),
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Customer->value,
            'branch_id' => null,
        ]);
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branch->id,
        ]);
    }
}
