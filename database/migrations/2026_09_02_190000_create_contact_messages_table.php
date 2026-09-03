<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pulled forward from lote 3 into lote 1 (Anyerson, 2026-09-02): the
     * /contacto form was built with a disabled submit button because this
     * table didn't exist yet. "channel" is "web" for every row today (the
     * public contact form is the only source); the column exists so a
     * future WhatsApp/Instagram inbox integration doesn't need a schema
     * change. "privacy_consent_at" is NOT NULL: Ley 29733 (Perú) requires
     * proof of consent, not just a passed validation rule, so we store WHEN
     * it was given alongside the message — never derived after the fact.
     */
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('nuevo');
            $table->string('channel')->default('web');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('privacy_consent_at');
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
