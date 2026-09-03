<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'subject' => $this->faker->randomElement(['reserva', 'consulta', 'modificacion', 'otro']),
            'message' => $this->faker->paragraph(),
            'status' => ContactMessageStatus::Nuevo,
            'channel' => 'web',
            'ip_address' => $this->faker->ipv4(),
            'privacy_consent_at' => now(),
        ];
    }
}
