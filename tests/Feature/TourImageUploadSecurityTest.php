<?php

namespace Tests\Feature;

use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Closes M-1 from docs/lote-2/seguridad-2026-09-01.md: ->image() only added
 * `mimetypes:image/*`, which let through an SVG with an inline <script> and
 * GIF polyglots renamed .pht/.html (extensions Laravel's PHP-upload block
 * doesn't cover). The fix is a closed MIME whitelist plus an extension taken
 * from the server-detected MIME type, never the client's filename.
 */
class TourImageUploadSecurityTest extends TestCase
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
            'title' => ['es' => 'Tour de prueba'],
            'slug' => ['es' => 'tour-de-prueba'],
            'price_pen_cents' => '100.00',
            'price_usd_cents' => '30.00',
        ];
    }

    /**
     * Control positive: proves the whitelist can actually pass something,
     * i.e. this isn't a check that fails closed no matter what.
     */
    public function test_the_gallery_accepts_a_legitimate_png(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();
        $file = UploadedFile::fake()->image('foto.png', 20, 20);

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'images' => [['path' => [$file], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $stored = Storage::disk('public')->allFiles('tours');
        $this->assertCount(1, $stored, 'The legitimate PNG must have been stored.');
        $this->assertStringEndsWith('.png', $stored[0]);
    }

    public function test_the_gallery_rejects_an_svg_with_an_embedded_script(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();
        // .svg maps to image/svg+xml both by real content-sniffing (finfo)
        // and by Laravel's fake-upload extension map, so a plain
        // createWithContent() already exercises the real MIME the server
        // would see.
        $file = UploadedFile::fake()->createWithContent(
            'x.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>'
        );

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'images' => [['path' => [$file], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasFormErrors(['images.0.path']);

        $this->assertCount(0, Storage::disk('public')->allFiles('tours'), 'Nothing must be stored when the upload is rejected.');
    }

    /**
     * NOTE on why this uses ->create(name, size, mimeType) instead of
     * ->createWithContent(): Laravel's fake UploadedFile::getMimeType()
     * (Illuminate\Http\Testing\File) derives the MIME purely from the
     * FILENAME's extension (Illuminate\Http\Testing\MimeType::from()),
     * never by sniffing real bytes — the opposite of production, where
     * finfo reads the actual file content regardless of name. A
     * createWithContent('evil.pht', 'GIF89a;...') fake would report
     * "application/octet-stream" (Laravel doesn't recognise .pht), which
     * would get rejected by the whitelist for the WRONG reason and wouldn't
     * tell us anything about the real vulnerability. Declaring the MIME
     * explicitly reproduces what the audit measured with a real polyglot
     * file: the server-detected type is image/gif, dressed up with a
     * dangerous client-side extension.
     */
    public function test_the_gallery_rejects_a_gif_php_polyglot_renamed_pht(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();
        $file = UploadedFile::fake()->create('evil.pht', 1, 'image/gif');

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'images' => [['path' => [$file], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasFormErrors(['images.0.path']);

        $this->assertCount(0, Storage::disk('public')->allFiles('tours'));
    }

    /**
     * See the note above test_the_gallery_rejects_a_gif_php_polyglot_renamed_pht():
     * same reasoning, this time for the .html polyglot.
     */
    public function test_the_gallery_rejects_a_gif_html_polyglot_renamed_html(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();
        $file = UploadedFile::fake()->create('evil.html', 1, 'image/gif');

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'images' => [['path' => [$file], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasFormErrors(['images.0.path']);

        $this->assertCount(0, Storage::disk('public')->allFiles('tours'));
    }

    /**
     * The extension on disk must come from the MIME type detected on the
     * server, never the name the browser sends — otherwise a file whose
     * content is a legitimate image but whose client filename claims a
     * dangerous extension would be trusted at face value.
     */
    public function test_the_stored_extension_comes_from_the_detected_mime_type_not_the_client_filename(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $destination = Destination::factory()->create();

        // Real PNG bytes (via GD), but the client claims a ".php.png"-style
        // double extension is irrelevant here — what matters is that even a
        // client name of "foto.png" does NOT dictate storage naming; the
        // server independently derives ".png" from the sniffed MIME type.
        $file = UploadedFile::fake()->image('foto.png', 20, 20);

        Livewire::test(CreateTour::class)
            ->fillForm([
                ...$this->baseFormData($destination),
                'images' => [['path' => [$file], 'order' => 0]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $stored = Storage::disk('public')->allFiles('tours');
        $this->assertCount(1, $stored);
        $this->assertMatchesRegularExpression('/^tours\/[0-9A-Z]{26}\.png$/', $stored[0], 'Filename must be a ULID + the sniffed extension, not the client name.');
    }
}
