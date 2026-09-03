<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;

/**
 * Closes M-1 (docs/lote-2/seguridad-2026-09-01.md) for every image-upload
 * field added AFTER the tour gallery: Filament's ->image() only adds
 * `mimetypes:image/*`, which lets an SVG carrying an inline <script> through,
 * and GIF/PHP or GIF/HTML polyglots renamed .pht/.html slip past Laravel's
 * extension-based PHP-upload block entirely. The fix is a closed MIME
 * whitelist plus a stored extension derived from the MIME type the server
 * actually detects (finfo, via UploadedFile::getMimeType()) — never the
 * client's filename or its claimed extension.
 *
 * `App\Filament\Resources\Tours\Schemas\TourForm` predates this helper and
 * keeps its own inline copy of the same logic. It is left untouched on
 * purpose: it already has its own green security suite
 * (tests/Feature/TourImageUploadSecurityTest.php) and refactoring
 * already-tested code wasn't in scope for the batch that added this helper
 * (docs/lote-1/01-esquema-lote1.md, S3). Every NEW upload field
 * (Destination/Experience cover image, TeamMember photo) uses this instead
 * of reimplementing it.
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
