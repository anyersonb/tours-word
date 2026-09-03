<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Deletes the model's stored file from the "public" disk whenever the
 * model itself is deleted, so removing a record through the CMS never
 * leaves an orphaned file behind in storage (defecto Bajo, docs/lote-1/
 * qa-cro-2026-09-02.md). Best-effort on purpose: a file that is already
 * missing (or a disk error) must never block deleting the database row --
 * the row is the source of truth, the file is a side effect of it.
 *
 * Does NOT run on a database-level cascade (e.g. tour_images.tour_id
 * ->cascadeOnDelete(), see the create_tour_images_table migration): MySQL
 * removes those rows directly without loading Eloquent models or firing
 * any event, so this hook never fires for them. Models reachable only
 * through a DB cascade must have their files cleaned up explicitly by the
 * parent (see Tour::booted()).
 */
trait DeletesStoredFileOnDelete
{
    protected static function bootDeletesStoredFileOnDelete(): void
    {
        static::deleting(function ($model): void {
            $model->deleteStoredFile();
        });
    }

    /**
     * Public and idempotent on purpose: Tour::booted() calls this directly
     * on each TourImage before the DB cascade removes the rows, since the
     * "deleting" event above never fires for a cascaded delete.
     */
    public function deleteStoredFile(): void
    {
        $path = $this->{$this->storedFileAttribute()};

        if ($path === null) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (Throwable $e) {
            Log::warning('model.stored_file_delete_failed', [
                'model' => static::class,
                'id' => $this->getKey(),
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Name of the attribute holding the disk path to delete.
     */
    abstract protected function storedFileAttribute(): string;
}
