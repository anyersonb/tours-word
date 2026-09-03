<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Closes S1 (docs/lote-1/01-esquema-lote1.md): the Home mockup's
     * "Destinos imperdibles" section shows exactly ONE photo per destination
     * (image + gradient + name overlay), never a gallery. A single nullable
     * column is deliberately chosen over a `destination_images` table like
     * `tour_images` — a gallery here would be complexity nobody asked for.
     * "cover_image_alt" is JSON/translatable for the same reason
     * `tour_images.alt` is: it's text a screen reader announces to the
     * public, not admin-only metadata.
     */
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->string('cover_image_path')->nullable()->after('description');
            $table->json('cover_image_alt')->nullable()->after('cover_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn(['cover_image_path', 'cover_image_alt']);
        });
    }
};
