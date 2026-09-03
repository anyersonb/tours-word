<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\TeamMember;
use App\Models\Tour;
use App\Models\TourImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Defecto Bajo de la auditoría (docs/lote-1/qa-cro-2026-09-02.md): borrar un
 * TeamMember/Destination/Experience/TourImage desde el CMS no borraba su
 * archivo físico, acumulando huérfanos en storage. Cubre también el caso
 * concreto que pide el brief: tour_images.tour_id tiene cascadeOnDelete()
 * a nivel de base de datos (ver create_tour_images_table), así que borrar
 * un Tour dispara la cascada de MySQL, NO el evento "deleting" de Eloquent
 * en TourImage -- Tour::booted() tiene que limpiar esos archivos a mano.
 *
 * Storage::fake('public') usa un disco real en un directorio temporal
 * (no un mock), así que exists()/missing() reflejan I/O real, no una
 * aserción que siempre daría el mismo resultado.
 */
class OrphanedFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_team_member_deletes_its_photo_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('team/photo.jpg', 'fake-bytes');

        $teamMember = TeamMember::factory()->create(['photo_path' => 'team/photo.jpg']);

        Storage::disk('public')->assertExists('team/photo.jpg');

        $teamMember->delete();

        Storage::disk('public')->assertMissing('team/photo.jpg');
        $this->assertDatabaseMissing('team_members', ['id' => $teamMember->id]);
    }

    public function test_deleting_a_team_member_without_a_photo_does_not_error(): void
    {
        Storage::fake('public');

        $teamMember = TeamMember::factory()->create(['photo_path' => null]);

        $teamMember->delete();

        $this->assertDatabaseMissing('team_members', ['id' => $teamMember->id]);
    }

    public function test_deleting_a_destination_deletes_its_cover_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('destinations/cover.jpg', 'fake-bytes');

        $destination = Destination::factory()->create(['cover_image_path' => 'destinations/cover.jpg']);

        Storage::disk('public')->assertExists('destinations/cover.jpg');

        $destination->delete();

        Storage::disk('public')->assertMissing('destinations/cover.jpg');
    }

    public function test_deleting_an_experience_deletes_its_cover_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('experiences/cover.jpg', 'fake-bytes');

        $experience = Experience::factory()->create(['cover_image_path' => 'experiences/cover.jpg']);

        Storage::disk('public')->assertExists('experiences/cover.jpg');

        $experience->delete();

        Storage::disk('public')->assertMissing('experiences/cover.jpg');
    }

    public function test_deleting_a_tour_image_directly_deletes_its_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tours/gallery/one.jpg', 'fake-bytes');

        $tourImage = TourImage::factory()->create(['path' => 'tours/gallery/one.jpg']);

        Storage::disk('public')->assertExists('tours/gallery/one.jpg');

        $tourImage->delete();

        Storage::disk('public')->assertMissing('tours/gallery/one.jpg');
    }

    /**
     * El caso concreto que pide el brief: la fila de tour_images la borra
     * la cascada de MySQL, no Eloquent -- si Tour::booted() no limpiara
     * los archivos a mano, este test fallaría con el archivo todavía en
     * disco aunque la fila ya no exista en la base de datos.
     */
    public function test_deleting_a_tour_deletes_its_images_files_despite_the_db_cascade(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tours/gallery/cascade.jpg', 'fake-bytes');

        $tour = Tour::factory()->create();
        $tourImage = TourImage::factory()->for($tour)->create(['path' => 'tours/gallery/cascade.jpg']);

        Storage::disk('public')->assertExists('tours/gallery/cascade.jpg');

        $tour->delete();

        $this->assertDatabaseMissing('tour_images', ['id' => $tourImage->id]);
        Storage::disk('public')->assertMissing('tours/gallery/cascade.jpg');
    }

    /**
     * Que el archivo ya no esté en disco no debe impedir borrar el
     * registro (borrado idempotente / best-effort).
     */
    public function test_deleting_a_record_whose_file_is_already_gone_from_disk_still_succeeds(): void
    {
        Storage::fake('public');
        // Never written to disk on purpose: simulates a file removed by
        // hand or lost in a previous incomplete deploy.
        $destination = Destination::factory()->create(['cover_image_path' => 'destinations/missing-already.jpg']);

        $destination->delete();

        $this->assertDatabaseMissing('destinations', ['id' => $destination->id]);
    }
}
