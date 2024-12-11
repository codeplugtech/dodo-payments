<?php

namespace Database\Factories;


use Codeplugtech\DodoPayments\DodoPayments;
use Codeplugtech\DodoPayments\Subscription;
use Codeplugtech\DodoPayments\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->word,
            'status' => $this->faker->randomElement(['active', 'cancelled', 'expired']),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}
