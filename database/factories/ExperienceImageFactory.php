<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\ExperienceImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExperienceImage>
 */
class ExperienceImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'experience_id' => Experience::factory(),
            'path' => 'experiences/sample/'.$this->faker->uuid().'.jpg',
            'alt' => ['es' => $this->faker->sentence(4)],
            'order' => 0,
        ];
    }
}
