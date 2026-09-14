<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Closes the lote-3 gap: destinations/show.blade.php shows a gallery
     * but Destination only ever had a single cover_image_path. Mirrors
     * tour_images exactly (database/migrations/..._create_tour_images_table.php):
     * ordered gallery, "alt" translatable per locale, "path" a disk path
     * (never a full URL, resolved through the model's accessor). A
     * dedicated table instead of reusing tour_images with a polymorphic
     * parent to keep the same non-polymorphic style already established by
     * tour_images -- this codebase has no polymorphic relations anywhere
     * else, and destinations/experiences don't share tours' cascade-on-
     * delete-of-tour concerns.
     */
    public function up(): void
    {
        Schema::create('destination_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->json('alt')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destination_images');
    }
};
