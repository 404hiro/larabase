<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'sender_user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'amount' => fake()->numberBetween(0, 10000),
            'sender_mode' => 'named',
            'sender_display_name' => fake()->name(),
            'status' => 'safe',
            'is_public' => false,
            'is_read' => false,
        ];
    }
}
