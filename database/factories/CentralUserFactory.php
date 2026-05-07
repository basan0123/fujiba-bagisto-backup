<?php

// database/factories/CentralUserFactory.php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CentralUserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }

    public function withPassword(string $plain): static
    {
        return $this->state(['password' => Hash::make($plain)]);
    }
}
