<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;

/**
 * Closes M-1 (docs/lote-2/seguridad-2026-09-01.md) for every image-upload
 * field: Filament's ->image() only adds `mimetypes:image/*`, which lets an
 * SVG carrying an inline <script> through, and GIF/PHP or GIF/HTML
 * polyglots renamed .pht/.html slip past Laravel's extension-based
 * PHP-upload block entirely. The fix is a closed MIME whitelist plus a
 * stored extension derived from the MIME type the server actually detects
 * (finfo, via UploadedFile::getMimeType()) — never the client's filename or
 * its claimed extension.
 *
 * `App\Filament\Resources\Tours\Schemas\TourForm` predated this helper and
 * kept its own inline copy of the same logic until F-2 part 2
 * (docs/lote-3/seguridad-2026-09-14.md, Alto) unified it here: two
 * implementations of one security rule diverge the moment either one
 * changes, and TourForm had already drifted before this fix. Every upload
 * field in the panel (tour gallery, Destination/Experience cover image +
 * gallery, TeamMember photo) goes through this class now — none of them
 * reimplement it. tests/Feature/SecureUploadCoverageTest.php is the
 * guardrail against a future field skipping it again.
 */
class SecureImageUpload
{
    /**
     * @var list<string>
     */
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function configure(FileUpload $upload, string $directory): FileUpload
    {
        return $upload
            ->acceptedFileTypes(self::ALLOWED_MIME_TYPES)
            ->maxSize(4096)
            ->disk('public')
            ->directory($directory)
            ->getUploadedFileNameForStorageUsing(
                fn ($file) => Str::ulid().'.'.match ($file->getMimeType()) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'bin',
                }
            );
    }
}
