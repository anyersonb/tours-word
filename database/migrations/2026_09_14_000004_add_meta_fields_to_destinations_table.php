<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Defecto 3 (auditoria CRO/SEO, lote SEO): a diferencia de Tour (ya
     * tenia meta_title/meta_description, ver create_tours_table), Destination
     * nunca tuvo donde la clienta escribiera un title/description de SEO
     * dedicados para la ficha -- las vistas usaban "name"/"description"
     * tal cual, y con "description" vacio (caso real: Cusco) la etiqueta
     * <meta name="description"> salia vacia. Mismo patron JSON traducible
     * que el resto de columnas de texto de este modelo (nullable: las
     * vistas caen a name/description cuando estan vacias, ver
     * destinations/show.blade.php).
     */
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->json('meta_title')->nullable()->after('description');
            $table->json('meta_description')->nullable()->after('meta_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
