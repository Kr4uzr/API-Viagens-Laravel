<?php

namespace Database\Factories;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelOrder>
 */
class TravelOrderFactory extends Factory
{
    public function definition(): array
    {
        $departureDate = fake()->dateTimeBetween('+1 week', '+3 months');
        $returnDate = fake()->dateTimeBetween($departureDate, '+6 months');

        return [
            'user_id' => User::factory(),
            'destination' => fake()->city() . ', ' . fake()->country(),
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'status' => TravelOrderStatus::Requested,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::Approved,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::Cancelled,
        ]);
    }
}
