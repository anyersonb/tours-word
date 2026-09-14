<?php

namespace Tests\Feature;

use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Models\Destination;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Write side of hueco #1 (itinerary): closes the rule "ningun campo que el
 * front lee puede quedar sin un sitio donde la clienta lo escriba" for the
 * Repeater added to App\Filament\Resources\Tours\Schemas\TourForm.
 */
class TourItineraryPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_a_tour_itinerary_can_be_created_from_the_panel(): void
    {
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();

        Livewire::test(CreateTour::class)
            ->fillForm([
                'destination_id' => $destination->id,
                'title' => ['es' => 'Tour con itinerario'],
                'slug' => ['es' => 'tour-con-itinerario-panel'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
                'itinerary' => [
                    'es' => [
                        ['title' => 'Dia 1: Llegada', 'description' => 'Recojo en el aeropuerto y traslado al hotel.'],
                        ['title' => 'Dia 2: Excursion', 'description' => 'Visita guiada al sitio arqueologico.'],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::query()->where('slug->es', 'tour-con-itinerario-panel')->first();

        $this->assertNotNull($tour);
        $this->assertSame([
            ['title' => 'Dia 1: Llegada', 'description' => 'Recojo en el aeropuerto y traslado al hotel.'],
            ['title' => 'Dia 2: Excursion', 'description' => 'Visita guiada al sitio arqueologico.'],
        ], $tour->getTranslation('itinerary', 'es', false));
    }

    public function test_a_tour_can_be_created_with_no_itinerary_steps_at_all(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Tour sin itinerario'],
                'slug' => ['es' => 'tour-sin-itinerario-panel'],
                'price_pen_cents' => '100.00',
                'price_usd_cents' => '30.00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::query()->where('slug->es', 'tour-sin-itinerario-panel')->first();

        $this->assertNotNull($tour);
        $this->assertSame([], $tour->getTranslation('itinerary', 'es', false) ?? []);
    }
}
