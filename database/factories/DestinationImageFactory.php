<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\DestinationImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DestinationImage>
 */
class DestinationImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'destination_id' => Destination::factory(),
            'path' => 'destinations/sample/'.$this->faker->uuid().'.jpg',
            'alt' => ['es' => $this->faker->sentence(4)],
            'order' => 0,
        ];
    }
}
