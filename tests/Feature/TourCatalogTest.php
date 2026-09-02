<?php

namespace Tests\Feature;

use App\Enums\TourDifficulty;
use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Models\Destination;
use App\Models\Tour;
use App\Models\TourSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TourCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * This is the project's own acceptance gate for lote 2: "que la clienta
     * pueda crear un tour y verlo publicado" — exercised through the actual
     * Filament panel component the client will use, not a bare factory call.
     */
    public function test_a_tour_can_be_created_from_the_panel_with_dual_currency_and_locale_fields(): void
    {
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();

        Livewire::test(CreateTour::class)
            ->fillForm([
                'destination_id' => $destination->id,
                'difficulty' => TourDifficulty::Moderado->value,
                'is_featured' => false,
                'is_published' => true,
                'order' => 1,
                'title' => ['es' => 'Valle Sagrado en un día'],
                'slug' => ['es' => 'valle-sagrado-en-un-dia'],
                'summary' => ['es' => 'Resumen de prueba.'],
                'description' => ['es' => 'Descripción de prueba.'],
                'duration_label' => ['es' => '10 horas'],
                'meeting_point' => ['es' => 'Plaza de Armas'],
                'inclusions' => ['es' => ['Transporte', 'Guía']],
                'exclusions' => ['es' => ['Almuerzo']],
                'meta_title' => ['es' => 'Valle Sagrado | Pacha Viva'],
                'meta_description' => ['es' => 'Meta descripción de prueba.'],
                'price_pen_cents' => '150.00',
                'price_usd_cents' => '40.00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::query()->where('slug->es', 'valle-sagrado-en-un-dia')->first();

        $this->assertNotNull($tour, 'The tour created through the panel must be queryable afterwards.');
        $this->assertTrue($tour->is_published);
        $this->assertSame('Valle Sagrado en un día', $tour->title);
        $this->assertSame(15000, $tour->price_pen_cents);
        $this->assertSame(4000, $tour->price_usd_cents);
        $this->assertSame('S/ 150.00', $tour->priceInPen()->format());
        $this->assertSame('US$ 40.00', $tour->priceInUsd()->format());
        $this->assertSame(['Transporte', 'Guía'], $tour->getTranslation('inclusions', 'es'));
        $this->assertSame($destination->id, $tour->destination_id);
    }

    public function test_the_panel_rejects_a_duplicate_slug_in_the_same_locale(): void
    {
        $this->actingAs($this->admin());

        Tour::factory()->create(['slug' => ['es' => 'tour-duplicado']]);

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => ['es' => 'Otro tour'],
                'slug' => ['es' => 'tour-duplicado'],
                'price_pen_cents' => '10.00',
                'price_usd_cents' => '3.00',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug.es']);

        $this->assertSame(
            1,
            Tour::query()->where('slug->es', 'tour-duplicado')->count(),
            'No second tour should have been created with the same ES slug.'
        );
    }

    public function test_changing_a_tour_slug_keeps_the_previous_one_in_history(): void
    {
        $tour = Tour::factory()->create(['slug' => ['es' => 'tour-original']]);

        $tour->update(['slug' => ['es' => 'tour-nuevo-nombre']]);

        $this->assertDatabaseHas('tour_slug_histories', [
            'tour_id' => $tour->id,
            'locale' => 'es',
            'slug' => 'tour-original',
        ]);

        $this->assertSame('tour-nuevo-nombre', $tour->fresh()->slug);
        $this->assertSame(1, TourSlugHistory::query()->where('tour_id', $tour->id)->count());
    }

    public function test_slug_taken_ignores_the_record_being_edited(): void
    {
        $tour = Tour::factory()->create(['slug' => ['es' => 'mi-tour']]);

        $this->assertFalse(Tour::slugTaken('es', 'mi-tour', $tour->id));
        $this->assertTrue(Tour::slugTaken('es', 'mi-tour'));
        $this->assertTrue(Tour::slugTaken('es', 'mi-tour', $tour->id + 999));
    }
}
