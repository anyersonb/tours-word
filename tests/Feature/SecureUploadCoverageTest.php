<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

/**
 * F-2 part 2 (docs/lote-3/seguridad-2026-09-14.md, Alto). Every image
 * upload field in the panel must be configured through
 * App\Filament\Support\SecureImageUpload::configure() — never a raw
 * FileUpload::make() (which defaults to no MIME restriction at all) nor a
 * ->image() call (which only adds `mimetypes:image/*`, letting an SVG with
 * an inline <script> or a GIF/PHP polyglot through). TourForm used to keep
 * its OWN inline copy of the exact same whitelist logic instead of using
 * the helper — functionally identical today, but two copies of one
 * security rule diverge the moment either one changes and nobody notices
 * until an audit re-derives it by hand (exactly what happened here).
 *
 * This is a static scan of the SOURCE, not a runtime behavioural check:
 * the whole point is to catch a field that reimplements the same
 * whitelist "correctly" (which would still pass every functional upload
 * test) but through a second, driftable copy. A behavioural test can never
 * catch that class of regression — only reading the code that wires the
 * field can.
 */
class SecureUploadCoverageTest extends TestCase
{
    public function test_every_fileupload_field_in_the_panel_is_configured_through_the_secure_helper(): void
    {
        $violations = [];

        foreach ($this->filamentSchemaFiles() as $file) {
            $contents = $file->getContents();

            // Every occurrence of "FileUpload::make(" in the file...
            preg_match_all('/FileUpload::make\(/', $contents, $allMatches, PREG_OFFSET_CAPTURE);

            // ...must be immediately preceded (allowing only whitespace)
            // by "SecureImageUpload::configure(". This is exactly the shape
            // every compliant field already has:
            //   SecureImageUpload::configure(
            //       FileUpload::make('path')->label('Imagen'),
            //       'directory'
            //   )
            preg_match_all(
                '/SecureImageUpload::configure\(\s*FileUpload::make\(/',
                $contents,
                $compliantMatches,
                PREG_OFFSET_CAPTURE
            );

            $totalFields = count($allMatches[0]);
            $compliantFields = count($compliantMatches[0]);

            if ($compliantFields < $totalFields) {
                $violations[] = sprintf(
                    '%s: %d de %d campo(s) FileUpload::make() NO pasan por SecureImageUpload::configure().',
                    $file->getRelativePathname(),
                    $totalFields - $compliantFields,
                    $totalFields
                );
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Cada FileUpload::make() del panel debe envolverse en SecureImageUpload::configure().\n".implode("\n", $violations)
        );
    }

    /**
     * Control positivo: si esta prueba nunca encontrara ningún
     * FileUpload::make(), el test anterior "pasaría" sin haber comprobado
     * nada (extracción vacía = falla, no OK). Hoy hay 6 campos reales
     * (tour gallery, destination cover+gallery, experience cover+gallery,
     * team member photo) repartidos en 3 archivos.
     */
    public function test_the_scan_actually_finds_fileupload_fields_to_check(): void
    {
        $total = 0;

        foreach ($this->filamentSchemaFiles() as $file) {
            $total += preg_match_all('/FileUpload::make\(/', $file->getContents());
        }

        $this->assertGreaterThan(
            0,
            $total,
            'El escaneo no encontró ningún FileUpload::make() -- probablemente está mirando la carpeta equivocada.'
        );
    }

    /**
     * @return list<SplFileInfo>
     */
    private function filamentSchemaFiles(): array
    {
        return File::allFiles(app_path('Filament'));
    }
}
