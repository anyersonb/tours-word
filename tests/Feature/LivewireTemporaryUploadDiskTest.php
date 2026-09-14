<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * F-4 (docs/lote-3/seguridad-2026-09-14.md, Medio). Without config/
 * livewire.php pinning 'temporary_file_upload.disk', Livewire resolves the
 * temp-upload disk from config('filesystems.default') (FILESYSTEM_DISK) —
 * an unrelated .env value that, if ever set to "public" in production
 * (a plausible "images aren't showing" fix someone applies in good faith),
 * would move the UNRESTRICTED temporary-upload endpoint
 * (livewire/upload-file accepts any file type) inside the docroot, behind
 * only the .htaccess added for F-2. Pinning the disk means this vector
 * closes regardless of what FILESYSTEM_DISK is set to.
 */
class LivewireTemporaryUploadDiskTest extends TestCase
{
    public function test_the_temporary_upload_disk_is_pinned_to_local_regardless_of_the_default_filesystem_disk(): void
    {
        // Control negativo: simula justo el cambio de .env que abriría el
        // vector si el disco temporal no estuviera fijado por su cuenta.
        config(['filesystems.default' => 'public']);

        $this->assertSame('local', config('livewire.temporary_file_upload.disk'));
    }

    public function test_the_temporary_upload_rate_limit_survived_pinning_the_disk(): void
    {
        // mergeConfigFrom() no hace deep-merge: fijar "disk" sin repetir el
        // resto de temporary_file_upload habría tirado silenciosamente el
        // throttle de subida y la limpieza de archivos de 24h.
        $this->assertSame('throttle:60,1', config('livewire.temporary_file_upload.middleware'));
        $this->assertTrue(config('livewire.temporary_file_upload.cleanup'));
    }

    public function test_the_local_disk_root_stays_outside_the_public_docroot(): void
    {
        $localRoot = config('filesystems.disks.local.root');

        $this->assertStringNotContainsString(
            'public'.DIRECTORY_SEPARATOR.'storage',
            $localRoot,
            'El disco "local" no debe apuntar dentro de public/storage.'
        );
    }
}
