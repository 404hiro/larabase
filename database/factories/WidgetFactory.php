<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Widget>
 */
class WidgetFactory extends Factory
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
            'type' => 'link',
            'content' => $this->faker->url(),
            'thumbnail_url' => null,
            'x' => 0,
            'y' => 0,
            'w' => 1,
            'h' => 2,
            'x_mobile' => 0,
            'y_mobile' => 0,
            'w_mobile' => 1,
            'h_mobile' => 2,
            'settings' => [],
        ];
    }
}
