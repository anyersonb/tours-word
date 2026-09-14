<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DEF-A (docs/lote-3/validacion-visual-2026-09-14.md, punto 4): the
     * "Reservar este tour" CTA on a tour page dropped the tour on the way to
     * /contacto, so the agency received leads that read "I want information"
     * without saying about what.
     *
     * TWO columns on purpose, not one:
     *
     * - "tour_id" is the live link (admin panel can open the tour, reports
     *   can group leads per tour). nullOnDelete() because a lead is not
     *   worth less once the tour is unpublished or deleted — it must never
     *   cascade a customer enquiry out of the inbox.
     * - "tour_title" is a SNAPSHOT of the title shown to the visitor at the
     *   moment they submitted, in the language they were browsing. Without
     *   it, deleting or renaming a tour silently rewrites history in the
     *   lead inbox and the agency loses the one fact the client asked for.
     *   It is also what the notification email prints, so the email stays
     *   truthful even if the catalog changes afterwards.
     *
     * Both nullable: the generic /contacto form (no tour in the URL) is
     * still a perfectly valid submission and must keep working unchanged.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('tour_id')
                ->nullable()
                ->after('subject')
                ->constrained('tours')
                ->nullOnDelete();

            $table->string('tour_title')->nullable()->after('tour_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tour_id');
            $table->dropColumn('tour_title');
        });
    }
};
