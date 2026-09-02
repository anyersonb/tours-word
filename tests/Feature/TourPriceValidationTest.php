<?php

namespace Tests\Feature;

use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Closes B-1 from docs/lote-2/seguridad-2026-09-01.md: price columns are
 * unsignedInteger, so the database already refuses a negative or overflowing
 * price — but it did so with an uncaught QueryException (SQLSTATE[22003]),
 * a 500 in production instead of a Spanish validation message.
 */
class TourPriceValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function baseFormData(Destination $destination): array
    {
        return [
            'destination_id' => $destination->id,
            'title' => ['es' => 'Tour de prueba de precio'],
            'slug' => ['es' => 'tour-de-prueba-de-precio'],
        ];
    }

    public function test_the_panel_rejects_a_negative_pen_price(): void
    {
        $this->actingAs($this->admin());
        $destination = Destination::factory()->create();

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'price_pen_cents' => '-150',
                'price_usd_cents' => '40.00',
            ])
            ->call('create')
            ->assertHasFormErrors(['price_pen_cents']);
    }

    public function test_the_panel_rejects_a_pen_price_over_the_column_ceiling(): void
    {
        $this->actingAs($this->admin());
        $destination = Destination::factory()->create();

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'price_pen_cents' => '99999999',
                'price_usd_cents' => '40.00',
            ])
            ->call('create')
            ->assertHasFormErrors(['price_pen_cents']);
    }

    public function test_the_panel_rejects_a_negative_usd_price(): void
    {
        $this->actingAs($this->admin());
        $destination = Destination::factory()->create();

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'price_pen_cents' => '150.00',
                'price_usd_cents' => '-40',
            ])
            ->call('create')
            ->assertHasFormErrors(['price_usd_cents']);
    }

    /**
     * Control positive: an in-range price must still pass, proving the
     * bounds aren't rejecting everything.
     */
    public function test_the_panel_still_accepts_a_normal_price(): void
    {
        $this->actingAs($this->admin());
        $destination = Destination::factory()->create();

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'price_pen_cents' => '150.00',
                'price_usd_cents' => '40.00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }
}
