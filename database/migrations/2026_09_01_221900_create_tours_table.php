<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Translatable JSON columns (Spatie\Translatable\HasTranslations, see
     * App\Models\Tour): title, slug, summary, description, meeting_point,
     * inclusions, exclusions, duration_label, meta_title, meta_description.
     * Only "es" is populated in lote 2; "en"/"pt_BR" arrive in lote 5 without
     * touching this schema (see docs/lote-2/00-contrato-datos.md, sección i18n).
     *
     * Money: price_pen_cents / price_usd_cents are integers (cents), never
     * float. Both are entered directly by the client in the CMS — they are
     * NOT derived from settings.exchange_rate_pen_usd at save time. That
     * setting is a separate, general-purpose conversion rate (read/used by
     * App\Support\Money), kept for the future booking module which will
     * freeze the rate used at the moment of booking (documented, not built
     * here — see the contract doc).
     *
     * "difficulty" is a plain string backed by App\Enums\TourDifficulty: it
     * is a fixed taxonomy, not translatable content, so its label is resolved
     * through lang files instead of being stored per-locale.
     */
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')
                ->nullable()
                ->constrained('destinations')
                ->nullOnDelete();

            $table->json('title');
            $table->json('slug');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->json('duration_label')->nullable();
            $table->string('difficulty')->nullable();
            $table->json('meeting_point')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();

            $table->unsignedInteger('price_pen_cents')->default(0);
            $table->unsignedInteger('price_usd_cents')->default(0);

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('order')->default(0);

            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
