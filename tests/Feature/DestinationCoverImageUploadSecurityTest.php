<?php

namespace Tests\Feature;

use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\Concerns\AssertsSecureImageUploads;
use Tests\TestCase;

/**
 * Applies the same defense TourImageUploadSecurityTest proved for the tour
 * gallery (M-1, docs/lote-2/seguridad-2026-09-01.md) to the destination cover
 * image added in S1/S3 (docs/lote-1/01-esquema-lote1.md). A new upload field
 * is a new attack surface — the whitelist doesn't come for free just because
 * it's used elsewhere.
 */
class DestinationCoverImageUploadSecurityTest extends TestCase
{
    use AssertsSecureImageUploads;
    use RefreshDatabase;

    protected function imageField(): string
    {
        return 'cover_image_path';
    }

    protected function storageDirectory(): string
    {
        return 'destinations';
    }

    protected function fillForm(array $formOverrides): Testable
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        return Livewire::test(CreateDestination::class)
            ->fillForm([
                'name' => ['es' => 'Destino de prueba'],
                'slug' => ['es' => 'destino-de-prueba'],
                'is_published' => true,
                'order' => 1,
                ...$formOverrides,
            ]);
    }
}
