<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * For tests only — the team starts empty in every environment (S2, see
 * App\Models\TeamMember). Never invoked from a seeder.
 *
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['es' => $this->faker->name()],
            'role' => ['es' => $this->faker->jobTitle()],
            'description' => ['es' => $this->faker->optional()->sentence()],
            'photo_path' => null,
            'photo_alt' => ['es' => null],
            'instagram_url' => null,
            'facebook_url' => null,
            'whatsapp_url' => null,
            'is_published' => true,
            'order' => 0,
        ];
    }
}
