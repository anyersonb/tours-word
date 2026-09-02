<?php

namespace Tests\Feature;

use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Experiences\Pages\CreateExperience;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DestinationAndExperienceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_destination_can_be_created_from_the_panel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => ['es' => 'Puno'],
                'slug' => ['es' => 'puno'],
                'is_published' => true,
                'order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $destination = Destination::query()->where('slug->es', 'puno')->first();

        $this->assertNotNull($destination);
        $this->assertSame('Puno', $destination->name);
        $this->assertTrue($destination->is_published);
    }

    public function test_an_experience_can_be_created_from_the_panel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateExperience::class)
            ->fillForm([
                'name' => ['es' => 'Aventura'],
                'slug' => ['es' => 'aventura'],
                'is_published' => true,
                'order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $experience = Experience::query()->where('slug->es', 'aventura')->first();

        $this->assertNotNull($experience);
        $this->assertSame('Aventura', $experience->name);
    }
}
