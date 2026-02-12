<?php

namespace Database\Factories;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'role' => UserRole::User,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    public function companyAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_role' => CompanyRole::Admin,
        ]);
    }

    public function engineer(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_role' => CompanyRole::Engineer,
        ]);
    }

    public function companyViewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_role' => CompanyRole::Viewer,
        ]);
    }
}
