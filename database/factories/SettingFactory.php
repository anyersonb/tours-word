<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => (string) $this->faker->randomNumber(3),
            'type' => 'string',
            'group' => null,
            'description' => null,
        ];
    }

    public function exchangeRate(float $rate = 3.75): static
    {
        return $this->state([
            'key' => 'exchange_rate_pen_usd',
            'value' => (string) $rate,
            'type' => 'float',
            'group' => 'moneda',
            'description' => 'Cuántos soles equivalen a 1 dólar. Valor fijo editable, no viene de una API.',
        ]);
    }
}
