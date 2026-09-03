<?php

namespace Tests\Feature;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Nuestro equipo" (S2, docs/lote-1/01-esquema-lote1.md): translatable
 * name/role/description/photo_alt, plain (non-translatable) social links,
 * and the published()/ordered() scopes nosotros.blade.php relies on
 * (`TeamMember::query()->published()->ordered()->get()`).
 */
class TeamMemberCatalogCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_member_can_be_created_with_translatable_fields_and_plain_social_links(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        // photo_path is ->required() in TeamMemberForm: a real photo is
        // needed for the create call to pass validation, same as
        // DestinationAndExperienceCatalogTest and
        // TeamMemberPhotoUploadSecurityTest do it.
        $photo = UploadedFile::fake()->image('rosa.jpg', 40, 40);

        Livewire::test(CreateTeamMember::class)
            ->fillForm([
                'photo_path' => $photo,
                'name' => ['es' => 'Rosa Quispe'],
                'role' => ['es' => 'Guía de montaña'],
                'description' => ['es' => 'Diez años guiando el Camino Inca.'],
                'photo_alt' => ['es' => 'Retrato de Rosa Quispe'],
                'instagram_url' => 'https://instagram.com/rosaquispe',
                'facebook_url' => 'https://facebook.com/rosaquispe',
                'whatsapp_url' => 'https://wa.me/51999888777',
                'is_published' => true,
                'order' => 2,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $member = TeamMember::query()->where('order', 2)->first();

        $this->assertNotNull($member);
        $this->assertSame('Rosa Quispe', $member->getTranslation('name', 'es', false));
        $this->assertSame('Guía de montaña', $member->getTranslation('role', 'es', false));
        $this->assertSame('Diez años guiando el Camino Inca.', $member->getTranslation('description', 'es', false));
        $this->assertSame('Retrato de Rosa Quispe', $member->getTranslation('photo_alt', 'es', false));
        // Social links are plain columns, not translatable JSON: a URL
        // doesn't change per locale (App\Models\TeamMember docblock).
        $this->assertSame('https://instagram.com/rosaquispe', $member->instagram_url);
        $this->assertSame('https://facebook.com/rosaquispe', $member->facebook_url);
        $this->assertSame('https://wa.me/51999888777', $member->whatsapp_url);
        $this->assertTrue($member->is_published);
        $this->assertNotNull($member->photo_path);
        $this->assertSame(
            Storage::disk('public')->url($member->photo_path),
            $member->photoUrl()
        );
    }

    public function test_the_published_scope_only_returns_published_members(): void
    {
        $published = TeamMember::factory()->create(['is_published' => true]);
        $unpublished = TeamMember::factory()->create(['is_published' => false]);

        $result = TeamMember::query()->published()->get();

        $this->assertTrue($result->contains('id', $published->id));
        $this->assertFalse($result->contains('id', $unpublished->id));
    }

    public function test_the_ordered_scope_returns_members_in_ascending_order_position(): void
    {
        $last = TeamMember::factory()->create(['order' => 2, 'name' => ['es' => 'Tercero']]);
        $first = TeamMember::factory()->create(['order' => 0, 'name' => ['es' => 'Primero']]);
        $middle = TeamMember::factory()->create(['order' => 1, 'name' => ['es' => 'Segundo']]);

        $result = TeamMember::query()->ordered()->get();

        $this->assertSame(
            [$first->id, $middle->id, $last->id],
            $result->pluck('id')->all()
        );
    }

    /**
     * The control this file exists to recover "tal cual" (Anyerson's brief):
     * "Nuestro equipo" starts empty on purpose because the four people in
     * the mockup (Carlos Mendoza, Ana Lucía Quispe, Luis Fernández, María
     * Torres) are AI-generated placeholders with an invented name — see the
     * create_team_members_table migration and App\Models\TeamMember. This
     * runs the REAL seeder pipeline (DatabaseSeeder, not a fixture built by
     * hand in the test) so a seeder change that reintroduces any of them
     * turns this test red.
     */
    public function test_the_real_seeder_pipeline_never_introduces_the_mockup_placeholder_people(): void
    {
        $this->seed(DatabaseSeeder::class);

        $forbiddenNames = [
            'Carlos Mendoza',
            'Ana Lucía Quispe',
            'Luis Fernández',
            'María Torres',
        ];

        $seededNames = TeamMember::query()->get()
            ->map(fn (TeamMember $member) => $member->getTranslation('name', 'es', false))
            ->all();

        foreach ($forbiddenNames as $forbiddenName) {
            $this->assertNotContains(
                $forbiddenName,
                $seededNames,
                "El seeder no debe crear a \"{$forbiddenName}\": es una persona inventada del mockup (cara generada por IA), no un dato real de la clienta."
            );
        }
    }
}
