<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\TeamMember;
use App\Models\TourImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Defecto Alto medido por el CRO (docs/lote-1/qa-cro-2026-09-02.md,
 * Defecto 5) y citado en S-04 del informe SEO: Storage::disk('public')->url()
 * armaba la URL absoluta desde APP_URL, y con APP_URL apuntando a un host
 * que no resuelve (http://tours-word.test) las imágenes del CMS se rompían
 * en silencio (ERR_NAME_NOT_RESOLVED, naturalWidth 0) sin que la página
 * fallara de ninguna otra forma.
 *
 * Usa el disco "public" REAL (nunca Storage::fake), porque el bug era
 * justamente en cómo se arma la URL a partir de config/filesystems.php --
 * un disco fake no pasa por esa configuración y el test no probaría nada.
 */
class PublicDiskRelativeUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory('test-relative-url');

        parent::tearDown();
    }

    /**
     * Control negativo explícito: reproduce la configuración vieja
     * (URL absoluta armada desde APP_URL) para probar que ESTE test sí
     * puede fallar. Si se revirtiera el fix de config/filesystems.php, esta
     * aserción detectaría el host roto en la URL.
     */
    public function test_the_old_app_url_based_config_would_have_produced_a_broken_absolute_host(): void
    {
        config(['filesystems.disks.public.url' => rtrim('http://tours-word.test', '/').'/storage']);

        $url = Storage::disk('public')->url('foo.png');

        $this->assertStringContainsString('tours-word.test', $url, 'Control: la config vieja SÍ debía producir el host roto.');
    }

    public function test_public_disk_url_is_relative_regardless_of_app_url(): void
    {
        config(['app.url' => 'http://a-host-that-does-not-resolve.invalid']);

        $url = Storage::disk('public')->url('destinations/example.png');

        $this->assertSame('/storage/destinations/example.png', $url);
        $this->assertStringNotContainsString('http', $url);
        $this->assertStringNotContainsString('a-host-that-does-not-resolve.invalid', $url);
    }

    public function test_destination_cover_image_url_is_relative_and_the_file_is_actually_reachable_on_disk(): void
    {
        $path = 'test-relative-url/destination.png';
        Storage::disk('public')->put($path, str_repeat('x', 128));

        $destination = Destination::factory()->create(['cover_image_path' => $path]);

        $this->assertSame("/storage/{$path}", $destination->coverImageUrl());
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertGreaterThan(0, Storage::disk('public')->size($path), 'The stored file must not be 0 bytes.');
    }

    public function test_experience_cover_image_url_is_relative(): void
    {
        $path = 'test-relative-url/experience.png';
        Storage::disk('public')->put($path, str_repeat('x', 128));

        $experience = Experience::factory()->create(['cover_image_path' => $path]);

        $this->assertSame("/storage/{$path}", $experience->coverImageUrl());
    }

    public function test_team_member_photo_url_is_relative(): void
    {
        $path = 'test-relative-url/team-member.png';
        Storage::disk('public')->put($path, str_repeat('x', 128));

        $teamMember = TeamMember::factory()->create(['photo_path' => $path]);

        $this->assertSame("/storage/{$path}", $teamMember->photoUrl());
    }

    public function test_tour_image_url_is_relative(): void
    {
        $path = 'test-relative-url/tour-image.png';
        Storage::disk('public')->put($path, str_repeat('x', 128));

        $tourImage = TourImage::factory()->create(['path' => $path]);

        $this->assertSame("/storage/{$path}", $tourImage->url());
    }
}
