<?php

namespace Tests\Feature;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\Concerns\AssertsSecureImageUploads;
use Tests\TestCase;

/**
 * Same defense as DestinationCoverImageUploadSecurityTest, applied to the
 * team member photo (S2/S3, docs/lote-1/01-esquema-lote1.md).
 */
class TeamMemberPhotoUploadSecurityTest extends TestCase
{
    use AssertsSecureImageUploads;
    use RefreshDatabase;

    protected function imageField(): string
    {
        return 'photo_path';
    }

    protected function storageDirectory(): string
    {
        return 'team';
    }

    protected function fillForm(array $formOverrides): Testable
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        return Livewire::test(CreateTeamMember::class)
            ->fillForm([
                'name' => ['es' => 'Persona de prueba'],
                'role' => ['es' => 'Guía'],
                'is_published' => true,
                'order' => 1,
                ...$formOverrides,
            ]);
    }
}
