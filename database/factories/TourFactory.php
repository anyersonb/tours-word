<?php

namespace Database\Factories;

use App\Enums\TourDifficulty;
use App\Models\Destination;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tour>
 */
class TourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Prices here are plain test fixtures (this factory only ever runs
     * against pachaviva_test, never seeds anything a visitor could see) — the
     * "no cifras inventadas que parezcan reales" rule applies to the public
     * demo seeder (DemoTourSeeder), which uses round, obviously-placeholder
     * amounts instead.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(3, true));

        return [
            'destination_id' => Destination::factory(),
            'title' => ['es' => $title],
            'slug' => ['es' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999)],
            'summary' => ['es' => $this->faker->sentence()],
            'description' => ['es' => $this->faker->paragraph()],
            'duration_label' => ['es' => $this->faker->randomElement(['4 horas', '8 horas', '2 días / 1 noche', '3 días / 2 noches'])],
            'difficulty' => $this->faker->randomElement(TourDifficulty::cases()),
            'meeting_point' => ['es' => $this->faker->address()],
            'inclusions' => ['es' => [$this->faker->sentence(3), $this->faker->sentence(3)]],
            'exclusions' => ['es' => [$this->faker->sentence(3)]],
            'price_pen_cents' => $this->faker->numberBetween(5000, 150000),
            'price_usd_cents' => $this->faker->numberBetween(1500, 40000),
            'is_featured' => false,
            'is_published' => true,
            'order' => 0,
            'meta_title' => ['es' => $title],
            'meta_description' => ['es' => $this->faker->sentence()],
        ];
    }

    /**
     * Marks the tour as sample data, per the project rule that content
     * without a real value from the client must be visibly flagged, never
     * invented as if it were real.
     */
    public function sample(): static
    {
        return $this->state(function (array $attributes) {
            $title = '[MUESTRA] '.($attributes['title']['es'] ?? $this->faker->words(3, true));

            return [
                'title' => ['es' => $title],
                'slug' => ['es' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999)],
            ];
        });
    }
}
