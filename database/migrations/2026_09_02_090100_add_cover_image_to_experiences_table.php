<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Same reasoning as the sibling migration on "destinations" (S1,
     * docs/lote-1/01-esquema-lote1.md): "Experiencias únicas" in the Home
     * mockup is one photo per experience, so a single cover column beats a
     * gallery table. "cover_image_alt" is JSON/translatable, same as
     * `tour_images.alt`.
     */
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->string('cover_image_path')->nullable()->after('description');
            $table->json('cover_image_alt')->nullable()->after('cover_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn(['cover_image_path', 'cover_image_alt']);
        });
    }
};
