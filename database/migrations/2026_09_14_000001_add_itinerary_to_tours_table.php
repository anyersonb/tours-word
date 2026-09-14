<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * "itinerary" closes the lote-3 gap: the ficha (tours/show.blade.php)
     * shows a day-by-day itinerary but Tour never had a column for it.
     * Same pattern already used by inclusions/exclusions (App\Models\Tour):
     * a translatable JSON column cast to 'array', holding a plain PHP array
     * of {title, description} steps for the current locale. A dedicated
     * table (tour_itinerary_steps, one row per day) was the other option
     * considered, but the content has no independent identity, ordering, or
     * relations of its own -- it's edited and read as a single unit per
     * tour, exactly like inclusions/exclusions already are. Following the
     * existing precedent keeps one pattern in the codebase instead of two.
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->json('itinerary')->nullable()->after('exclusions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('itinerary');
        });
    }
};
