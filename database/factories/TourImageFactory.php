<?php

namespace Database\Factories;

use App\Models\Tour;
use App\Models\TourImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourImage>
 */
class TourImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'path' => 'tours/sample/'.$this->faker->uuid().'.jpg',
            'alt' => ['es' => $this->faker->sentence(4)],
            'order' => 0,
        ];
    }
}
