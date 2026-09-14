<?php

namespace Tests\Feature;

use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Experiences\Pages\CreateExperience;
use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defecto 3 (auditoria cliente, 2026-09-14): la clienta pidio, con estas
 * palabras, que el campo "Meta título (SEO)" se llame algo que ella
 * entienda sin explicacion -- propuso "Título que aparece en Google".
 * Aplicado a tour/destino/experiencia y al campo de descripcion
 * equivalente ("Meta descripción (SEO)" -> "Descripción que aparece en
 * Google"), para no dejar la mitad del panel en jerga tecnica.
 */
class CmsFriendlyFieldLabelsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_the_tour_form_uses_the_clients_wording_instead_of_seo_jargon(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTour::class)
            ->assertSee('Título que aparece en Google')
            ->assertSee('Descripción que aparece en Google')
            ->assertDontSee('Meta título (SEO)')
            ->assertDontSee('Meta descripción (SEO)');
    }

    public function test_the_destination_form_uses_the_clients_wording_instead_of_seo_jargon(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateDestination::class)
            ->assertSee('Título que aparece en Google')
            ->assertSee('Descripción que aparece en Google')
            ->assertDontSee('Meta título (SEO)')
            ->assertDontSee('Meta descripción (SEO)');
    }

    public function test_the_experience_form_uses_the_clients_wording_instead_of_seo_jargon(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateExperience::class)
            ->assertSee('Título que aparece en Google')
            ->assertSee('Descripción que aparece en Google')
            ->assertDontSee('Meta título (SEO)')
            ->assertDontSee('Meta descripción (SEO)');
    }
}
