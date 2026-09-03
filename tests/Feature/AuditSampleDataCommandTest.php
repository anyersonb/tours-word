<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers S4 (docs/lote-1/01-esquema-lote1.md): the repeatable pre-deploy
 * audit born from tours.id=3 and users.id=2 in `pachaviva` (real lote-2 QA
 * artifacts, not hypothetical). The command must default to READ-ONLY
 * reporting — cleanup only happens when named explicitly via --clean-*.
 */
class AuditSampleDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_flags_a_published_featured_tour_without_the_sample_prefix_as_top_priority(): void
    {
        $destination = Destination::factory()->create();
        // MySQL/InnoDB never rolls back AUTO_INCREMENT on a transaction
        // rollback (RefreshDatabase wraps each test in one), so the ID this
        // row gets depends on how many tours earlier tests in the same
        // suite run created — never assume it's 1.
        $tour = Tour::factory()->create([
            'destination_id' => $destination->id,
            'title' => ['es' => 'Camino Inca Corto a Machu Picchu'],
            'is_published' => true,
            'is_featured' => true,
            'order' => 0,
        ]);

        // Two notes on how Laravel's artisan-test harness works here:
        // 1. expectsOutputToContain() must be chained BEFORE the terminating
        //    assertion (assertExitCode() is what actually runs the command
        //    and consumes its output); anything chained after it queues an
        //    expectation for a run that already happened and is never
        //    re-checked.
        // 2. Each expectsOutputToContain() is matched against ONE console
        //    write call (Mockery consumes that call for whichever
        //    expectation matches first). Both phrases below live on the
        //    SAME printed line, so asserting them as two separate calls
        //    would make the second one starve — the line only gets
        //    "consumed" once. One assertion covering the whole line avoids
        //    that.
        $this->artisan('data:audit-sample')
            ->expectsOutputToContain("prioridad de revisión: Tour id={$tour->id} \"Camino Inca Corto a Machu Picchu\" aparece primero en el carrusel de Home.")
            ->assertExitCode(0);
    }

    public function test_it_does_not_flag_content_that_carries_the_sample_prefix(): void
    {
        // The destination itself is real catalog taxonomy (not a marketing
        // claim, see DemoTourSeeder) and legitimately has no "[MUESTRA]"
        // prefix, so it's expected to show up as "not sample" alongside the
        // tour that DOES carry the prefix: 1 of 2, not 0.
        $destination = Destination::factory()->create();
        Tour::factory()->create([
            'destination_id' => $destination->id,
            'title' => ['es' => '[MUESTRA] Tour de catálogo'],
            'is_published' => true,
            'is_featured' => true,
            'order' => 0,
        ]);

        $this->artisan('data:audit-sample')
            ->expectsOutputToContain('Contenido sin prefijo "[MUESTRA]" (1 de 2)')
            ->doesntExpectOutputToContain('[MUESTRA] Tour de catálogo')
            ->assertExitCode(0);
    }

    public function test_it_flags_a_user_on_the_reserved_test_domain(): void
    {
        User::factory()->create(['email' => 'noadmin.qa@pachaviva.test', 'is_admin' => false]);
        User::factory()->create(['email' => 'clienta@empresareal.pe', 'is_admin' => true]);

        $this->artisan('data:audit-sample')
            ->expectsOutputToContain('candidato a --clean-test-users: id=')
            ->expectsOutputToContain('noadmin.qa@pachaviva.test')
            ->assertExitCode(0);
    }

    /**
     * The check that must be able to fail: running the command WITHOUT any
     * --clean-* flag must never mutate data. If this assertion is dropped,
     * a test suite could still pass green while the "default is read-only"
     * guarantee silently breaks.
     */
    public function test_running_without_flags_does_not_delete_or_modify_anything(): void
    {
        $qaUser = User::factory()->create(['email' => 'noadmin.qa@pachaviva.test', 'is_admin' => false]);
        $destination = Destination::factory()->create();
        $tour = Tour::factory()->create([
            'destination_id' => $destination->id,
            'title' => ['es' => 'Camino Inca Corto a Machu Picchu'],
            'is_published' => true,
            'is_featured' => true,
            'order' => 0,
        ]);

        $this->artisan('data:audit-sample')->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $qaUser->id]);
        $tour->refresh();
        $this->assertTrue($tour->is_published);
        $this->assertTrue($tour->is_featured);
    }

    public function test_clean_test_users_deletes_only_users_on_the_reserved_domain(): void
    {
        $qaUser = User::factory()->create(['email' => 'noadmin.qa@pachaviva.test', 'is_admin' => false]);
        $realAdmin = User::factory()->create(['email' => 'clienta@empresareal.pe', 'is_admin' => true]);

        $this->artisan('data:audit-sample', ['--clean-test-users' => true])->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $qaUser->id]);
        $this->assertDatabaseHas('users', ['id' => $realAdmin->id]);
    }

    public function test_clean_tour_ids_only_unpublishes_the_named_tour_and_leaves_others_untouched(): void
    {
        $destination = Destination::factory()->create();
        $qaTour = Tour::factory()->create([
            'destination_id' => $destination->id,
            'is_published' => true,
            'is_featured' => true,
        ]);
        $realTour = Tour::factory()->create([
            'destination_id' => $destination->id,
            'is_published' => true,
            'is_featured' => true,
        ]);

        $this->artisan('data:audit-sample', ['--clean-tour-ids' => (string) $qaTour->id])->assertExitCode(0);

        $qaTour->refresh();
        $realTour->refresh();

        $this->assertFalse($qaTour->is_published);
        $this->assertFalse($qaTour->is_featured);
        $this->assertTrue($realTour->is_published, 'A tour NOT named in --clean-tour-ids must survive untouched.');
        $this->assertTrue($realTour->is_featured);

        // Non-destructive: the row itself must still exist for review.
        $this->assertDatabaseHas('tours', ['id' => $qaTour->id]);
    }
}
