<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'industry' => fake()->randomElement(['Retail', 'Hospitality', 'Professional Services', 'E-commerce']),
            'website' => fake()->optional()->url(),
            'phone' => fake()->optional()->phoneNumber(),
            'country_code' => 'IN',
            'currency_code' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'status' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => false]);
    }
}
