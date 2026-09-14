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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * O-1 (docs/lote-3/seguridad-2026-09-14.md, Bajo). Destination and
 * Experience declared `->rule('alpha_dash')` on the slug field but never
 * validated uniqueness the way Tour::slugTaken() already did — nothing
 * stopped two destinations (or experiences) from sharing the same slug for
 * the same locale. Once lote 3 wired the public catalog to resolve by slug
 * (ResolvesBySlugByLocale::findBySlugForLocale() -> ->first()), the second
 * record with a duplicate slug becomes reachable to nobody: still published
 * in the CMS, invisible on the site.
 */
class DestinationAndExperienceSlugUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_destination_slug_taken_ignores_the_records_own_row_but_catches_a_duplicate(): void
    {
        $destination = Destination::factory()->create(['slug' => ['es' => 'cusco']]);

        $this->assertFalse(Destination::slugTaken('es', 'cusco', $destination->id));
        $this->assertTrue(Destination::slugTaken('es', 'cusco'));
        $this->assertTrue(Destination::slugTaken('es', 'cusco', $destination->id + 999));
    }

    public function test_experience_slug_taken_ignores_the_records_own_row_but_catches_a_duplicate(): void
    {
        $experience = Experience::factory()->create(['slug' => ['es' => 'trekking']]);

        $this->assertFalse(Experience::slugTaken('es', 'trekking', $experience->id));
        $this->assertTrue(Experience::slugTaken('es', 'trekking'));
        $this->assertTrue(Experience::slugTaken('es', 'trekking', $experience->id + 999));
    }

    /**
     * Same whitelist guard Tour::slugTaken() already had (docs/lote-2/
     * seguridad-2026-09-01.md, B-0): $locale is interpolated into the JSON
     * path, so it must be checked against config('cms.locales') before
     * touching the query, even though every current caller only ever
     * passes a value from that same config.
     */
    public function test_destination_slug_taken_rejects_a_locale_outside_the_schema(): void
    {
        $this->expectException(HttpException::class);

        Destination::slugTaken('es" OR "1"="1', 'cusco');
    }

    public function test_experience_slug_taken_rejects_a_locale_outside_the_schema(): void
    {
        $this->expectException(HttpException::class);

        Experience::slugTaken('es" OR "1"="1', 'trekking');
    }

    public function test_the_destination_panel_rejects_a_duplicate_spanish_slug(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        Destination::factory()->create(['slug' => ['es' => 'cusco']]);

        Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => ['es' => 'Otro Cusco'],
                'slug' => ['es' => 'cusco'],
                'cover_image_path' => UploadedFile::fake()->image('foto.png', 20, 20),
            ])
            ->call('create')
            ->assertHasFormErrors(['slug.es']);

        $this->assertSame(1, Destination::query()->where('slug->es', 'cusco')->count());
    }

    public function test_the_experience_panel_rejects_a_duplicate_spanish_slug(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        Experience::factory()->create(['slug' => ['es' => 'trekking']]);

        Livewire::test(CreateExperience::class)
            ->fillForm([
                'name' => ['es' => 'Otro Trekking'],
                'slug' => ['es' => 'trekking'],
                'cover_image_path' => UploadedFile::fake()->image('foto.png', 20, 20),
            ])
            ->call('create')
            ->assertHasFormErrors(['slug.es']);

        $this->assertSame(1, Experience::query()->where('slug->es', 'trekking')->count());
    }

    /**
     * Control positivo: un slug distinto sigue creándose sin errores — el
     * fix bloquea el duplicado, no cualquier creación.
     */
    public function test_the_destination_panel_still_accepts_a_unique_slug(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        Destination::factory()->create(['slug' => ['es' => 'cusco']]);

        Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => ['es' => 'Arequipa'],
                'slug' => ['es' => 'arequipa'],
                'cover_image_path' => UploadedFile::fake()->image('foto.png', 20, 20),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Destination::query()->where('slug->es', 'arequipa')->count());
    }
}
