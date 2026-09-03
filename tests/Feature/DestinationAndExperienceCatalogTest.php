<?php

namespace Tests\Feature;

use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Experiences\Pages\CreateExperience;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    /**
     * S1 (docs/lote-1/01-esquema-lote1.md): the cover image's "alt" text
     * must round-trip through the panel like any other translatable field,
     * and the public URL accessor must resolve once a path is stored —
     * "a field isn't done until the front reads it AND its own cache
     * invalidates without a manual clear" (project ground rule); this test
     * covers the data half of that contract, not the front rendering.
     */
    public function test_a_destination_cover_image_and_its_translatable_alt_text_round_trip(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $file = UploadedFile::fake()->image('cusco.png', 40, 40);

        Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => ['es' => 'Cusco'],
                'slug' => ['es' => 'cusco'],
                'cover_image_path' => $file,
                'cover_image_alt' => ['es' => 'Vista panorámica de Cusco al atardecer'],
                'is_published' => true,
                'order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $destination = Destination::query()->where('slug->es', 'cusco')->first();

        $this->assertNotNull($destination);
        $this->assertSame('Vista panorámica de Cusco al atardecer', $destination->cover_image_alt);
        $this->assertNotNull($destination->cover_image_path);
        $this->assertSame(
            Storage::disk('public')->url($destination->cover_image_path),
            $destination->coverImageUrl()
        );
    }
}
