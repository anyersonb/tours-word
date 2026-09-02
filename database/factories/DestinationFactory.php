<?php

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->city();

        return [
            'name' => ['es' => $name],
            'slug' => ['es' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 99999)],
            'description' => ['es' => $this->faker->optional()->sentence()],
            'is_published' => true,
            'order' => 0,
        ];
    }
}
