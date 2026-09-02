<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ['es' => ucfirst($name)],
            'slug' => ['es' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 99999)],
            'description' => ['es' => $this->faker->optional()->sentence()],
            'is_published' => true,
            'order' => 0,
        ];
    }
}
