<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mismo hueco y mismo arreglo que add_meta_fields_to_destinations_table
     * (ver esa migracion): Experience tampoco tenia meta_title/meta_description
     * dedicados -- caso real confirmado: Trekking con meta description vacia.
     */
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->json('meta_title')->nullable()->after('description');
            $table->json('meta_description')->nullable()->after('meta_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
