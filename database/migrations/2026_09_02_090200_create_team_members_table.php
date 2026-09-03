<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Closes S2 (docs/lote-1/01-esquema-lote1.md): "Nuestro equipo" in the
     * Nosotros mockup was missing entirely from the lote 2 data contract —
     * an omission, not a scope change. "name"/"role"/"description" are JSON
     * translatable columns (same Spatie pattern as Tour/Destination/
     * Experience); "photo_alt" is translatable for the same accessibility
     * reason as `tour_images.alt`. Social links are plain, non-translatable
     * strings: a URL doesn't change per locale.
     *
     * The table starts EMPTY on purpose — the four people in the mockup
     * (Carlos Mendoza, Ana Lucía Quispe, Luis Fernández, María Torres) are
     * AI-generated placeholders and must never be seeded, not even labeled
     * "[MUESTRA]", per the brief.
     */
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('role');
            $table->json('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->json('photo_alt')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('whatsapp_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
