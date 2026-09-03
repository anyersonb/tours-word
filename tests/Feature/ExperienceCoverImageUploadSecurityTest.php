<?php

namespace Tests\Feature;

use App\Filament\Resources\Experiences\Pages\CreateExperience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\Concerns\AssertsSecureImageUploads;
use Tests\TestCase;

/**
 * Same defense as DestinationCoverImageUploadSecurityTest, applied to the
 * experience cover image (S1/S3, docs/lote-1/01-esquema-lote1.md).
 */
class ExperienceCoverImageUploadSecurityTest extends TestCase
{
    use AssertsSecureImageUploads;
    use RefreshDatabase;

    protected function imageField(): string
    {
        return 'cover_image_path';
    }

    protected function storageDirectory(): string
    {
        return 'experiences';
    }

    protected function fillForm(array $formOverrides): Testable
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        return Livewire::test(CreateExperience::class)
            ->fillForm([
                'name' => ['es' => 'Experiencia de prueba'],
                'slug' => ['es' => 'experiencia-de-prueba'],
                'is_published' => true,
                'order' => 1,
                ...$formOverrides,
            ]);
    }
}
