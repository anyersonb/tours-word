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

/**
 * Write side of hueco #2 (gallery): round-trip + the SecureImageUpload
 * whitelist (same defense as TourImageUploadSecurityTest, M-1 docs/lote-2/
 * seguridad-2026-09-01.md) applied to the NEW Repeater field added to
 * DestinationForm/ExperienceForm. A new field is a new attack surface even
 * when it reuses an already-tested helper class.
 */
class DestinationAndExperienceGalleryPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_a_destination_gallery_image_round_trips_with_real_bytes_and_translatable_alt(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        // createWithContent + byte-count comparison: UploadedFile::fake()
        // ->create() reports a size but writes 0 bytes, which would let a
        // broken upload pass this test silently.
        $bytes = str_repeat('a', 2048);
        $file = UploadedFile::fake()->createWithContent('foto.jpg', $bytes);

        Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => ['es' => 'Destino con galeria'],
                'slug' => ['es' => 'destino-con-galeria-panel'],
                'gallery' => [['path' => [$file], 'alt' => ['es' => 'Foto de portada'], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $destination = Destination::query()->where('slug->es', 'destino-con-galeria-panel')->first();

        $this->assertNotNull($destination);
        $image = $destination->gallery->first();
        $this->assertNotNull($image);
        $this->assertSame('Foto de portada', $image->getTranslation('alt', 'es'));
        $this->assertSame(strlen($bytes), Storage::disk('public')->size($image->path));
        $this->assertSame(Storage::disk('public')->url($image->path), $image->url());
    }

    public function test_the_destination_gallery_rejects_a_gif_php_polyglot_renamed_pht(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $file = UploadedFile::fake()->create('evil.pht', 1, 'image/gif');

        Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => ['es' => 'Destino malicioso'],
                'slug' => ['es' => 'destino-malicioso-panel'],
                'gallery' => [['path' => [$file], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasFormErrors(['gallery.0.path']);

        $this->assertCount(0, Storage::disk('public')->allFiles('destinations'));
    }

    public function test_an_experience_gallery_image_round_trips_with_real_bytes_and_translatable_alt(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $bytes = str_repeat('b', 2048);
        $file = UploadedFile::fake()->createWithContent('foto.jpg', $bytes);

        Livewire::test(CreateExperience::class)
            ->fillForm([
                'name' => ['es' => 'Experiencia con galeria'],
                'slug' => ['es' => 'experiencia-con-galeria-panel'],
                'gallery' => [['path' => [$file], 'alt' => ['es' => 'Foto de experiencia'], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $experience = Experience::query()->where('slug->es', 'experiencia-con-galeria-panel')->first();

        $this->assertNotNull($experience);
        $image = $experience->gallery->first();
        $this->assertNotNull($image);
        $this->assertSame('Foto de experiencia', $image->getTranslation('alt', 'es'));
        $this->assertSame(strlen($bytes), Storage::disk('public')->size($image->path));
    }

    public function test_the_experience_gallery_rejects_an_svg_with_an_embedded_script(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $file = UploadedFile::fake()->createWithContent(
            'x.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>'
        );

        Livewire::test(CreateExperience::class)
            ->fillForm([
                'name' => ['es' => 'Experiencia maliciosa'],
                'slug' => ['es' => 'experiencia-maliciosa-panel'],
                'gallery' => [['path' => [$file], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasFormErrors(['gallery.0.path']);

        $this->assertCount(0, Storage::disk('public')->allFiles('experiences'));
    }
}
