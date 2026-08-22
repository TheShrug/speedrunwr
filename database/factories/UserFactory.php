<?php

namespace Database\Factories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'userName' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'verified' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
