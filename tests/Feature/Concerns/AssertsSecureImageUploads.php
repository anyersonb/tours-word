<?php

namespace Tests\Feature\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;

/**
 * Shared assertions for every image-upload field wired through
 * App\Filament\Support\SecureImageUpload (Destination/Experience cover image,
 * TeamMember photo). Mirrors, field by field, the checks
 * tests/Feature/TourImageUploadSecurityTest.php pioneered for the tour
 * gallery when M-1 (docs/lote-2/seguridad-2026-09-01.md) was closed: a
 * closed MIME whitelist plus an extension derived from the server-detected
 * MIME type, never the client's filename.
 *
 * Consuming test classes only need to implement fillForm() (mount the
 * Livewire "create" component, actingAs an admin, and fillForm() with the
 * resource's required fields merged with $formOverrides) plus two string
 * getters describing where the field writes.
 */
trait AssertsSecureImageUploads
{
    /**
     * @param  array<string, mixed>  $formOverrides
     */
    abstract protected function fillForm(array $formOverrides): Testable;

    abstract protected function imageField(): string;

    abstract protected function storageDirectory(): string;

    /**
     * Control positive: proves the whitelist can actually pass something,
     * i.e. this isn't a check that fails closed no matter what.
     */
    public function test_it_accepts_a_legitimate_png(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('foto.png', 20, 20);

        $this->fillForm([$this->imageField() => $file])
            ->call('create')
            ->assertHasNoFormErrors();

        $stored = Storage::disk('public')->allFiles($this->storageDirectory());
        $this->assertCount(1, $stored, 'The legitimate PNG must have been stored.');
        $this->assertStringEndsWith('.png', $stored[0]);
    }

    public function test_it_rejects_an_svg_with_an_embedded_script(): void
    {
        Storage::fake('public');
        // .svg maps to image/svg+xml both by real content-sniffing (finfo)
        // and by Laravel's fake-upload extension map, so a plain
        // createWithContent() already exercises the real MIME the server
        // would see.
        $file = UploadedFile::fake()->createWithContent(
            'x.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>'
        );

        $this->fillForm([$this->imageField() => $file])
            ->call('create')
            ->assertHasFormErrors([$this->imageField()]);

        $this->assertCount(0, Storage::disk('public')->allFiles($this->storageDirectory()), 'Nothing must be stored when the upload is rejected.');
    }

    /**
     * NOTE on why this uses ->create(name, size, mimeType) instead of
     * ->createWithContent(): Laravel's fake UploadedFile::getMimeType()
     * derives the MIME purely from the FILENAME's extension, never by
     * sniffing real bytes — the opposite of production, where finfo reads
     * the actual file content regardless of name. Declaring the MIME
     * explicitly reproduces what the audit measured with a real polyglot
     * file: the server-detected type is image/gif, dressed up with a
     * dangerous client-side extension.
     */
    public function test_it_rejects_a_gif_php_polyglot_renamed_pht(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('evil.pht', 1, 'image/gif');

        $this->fillForm([$this->imageField() => $file])
            ->call('create')
            ->assertHasFormErrors([$this->imageField()]);

        $this->assertCount(0, Storage::disk('public')->allFiles($this->storageDirectory()));
    }

    /**
     * Same reasoning as test_it_rejects_a_gif_php_polyglot_renamed_pht():
     * a GIF/HTML polyglot renamed .html.
     */
    public function test_it_rejects_a_gif_html_polyglot_renamed_html(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('evil.html', 1, 'image/gif');

        $this->fillForm([$this->imageField() => $file])
            ->call('create')
            ->assertHasFormErrors([$this->imageField()]);

        $this->assertCount(0, Storage::disk('public')->allFiles($this->storageDirectory()));
    }

    /**
     * The extension on disk must come from the MIME type detected on the
     * server, never the name the browser sends.
     */
    public function test_the_stored_extension_comes_from_the_detected_mime_type_not_the_client_filename(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('foto.png', 20, 20);

        $this->fillForm([$this->imageField() => $file])
            ->call('create')
            ->assertHasNoFormErrors();

        $stored = Storage::disk('public')->allFiles($this->storageDirectory());
        $this->assertCount(1, $stored);
        $this->assertMatchesRegularExpression(
            '#^'.preg_quote($this->storageDirectory(), '#').'/[0-9A-Z]{26}\.png$#',
            $stored[0],
            'Filename must be a ULID + the sniffed extension, not the client name.'
        );
    }
}
