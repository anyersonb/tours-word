<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records every previous (locale, slug) a tour has ever had. Written by
     * App\Models\Tour::bootHasSlugHistory() whenever a locale's slug changes.
     * This lote does NOT build the public 301 redirect (no public routes
     * exist yet — that's lote 3/5), but the history is captured from day one
     * so nothing is lost: a future middleware can look up an old slug here
     * and issue the redirect without having to reconstruct history later.
     */
    public function up(): void
    {
        Schema::create('tour_slug_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('slug');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['locale', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_slug_histories');
    }
};
