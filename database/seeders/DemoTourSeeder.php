<?php

namespace Database\Seeders;

use App\Enums\TourDifficulty;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Illuminate\Database\Seeder;

/**
 * Sample catalog data so the client can see the CMS populated. Every tour
 * title is prefixed "[MUESTRA]" and its prices are round placeholder
 * numbers — no invented "real" figures (years of experience, traveler
 * counts, awards) anywhere, per the project's hard rule against fabricated
 * credibility claims. Destinations/experiences use real Peruvian place
 * names, which are plain catalog taxonomy, not marketing claims.
 *
 * Note: firstOrCreate() is avoided here on purpose — its "where" attributes
 * (JSON-path keys like "slug->es") would otherwise be merged into the
 * create() payload as literal, non-fillable keys. Look up first, create
 * only if missing.
 */
class DemoTourSeeder extends Seeder
{
    public function run(): void
    {
        $cusco = Destination::query()->where('slug->es', 'cusco')->first()
            ?? Destination::create(['name' => ['es' => 'Cusco'], 'slug' => ['es' => 'cusco'], 'is_published' => true, 'order' => 1]);

        $arequipa = Destination::query()->where('slug->es', 'arequipa')->first()
            ?? Destination::create(['name' => ['es' => 'Arequipa'], 'slug' => ['es' => 'arequipa'], 'is_published' => true, 'order' => 2]);

        $trekking = Experience::query()->where('slug->es', 'trekking')->first()
            ?? Experience::create(['name' => ['es' => 'Trekking'], 'slug' => ['es' => 'trekking'], 'is_published' => true, 'order' => 1]);

        $gastronomia = Experience::query()->where('slug->es', 'gastronomia')->first()
            ?? Experience::create(['name' => ['es' => 'Gastronomía'], 'slug' => ['es' => 'gastronomia'], 'is_published' => true, 'order' => 2]);

        $cultura = Experience::query()->where('slug->es', 'cultura')->first()
            ?? Experience::create(['name' => ['es' => 'Cultura'], 'slug' => ['es' => 'cultura'], 'is_published' => true, 'order' => 3]);

        $tourOne = Tour::query()->where('slug->es', 'muestra-camino-inca-4-dias')->first()
            ?? Tour::create([
                'destination_id' => $cusco->id,
                'title' => ['es' => '[MUESTRA] Camino Inca 4 días'],
                'slug' => ['es' => 'muestra-camino-inca-4-dias'],
                'summary' => ['es' => 'Tour de muestra: trekking clásico hasta Machu Picchu.'],
                'description' => ['es' => 'Contenido de muestra para probar el catálogo. Este texto NO fue provisto por la clienta.'],
                'duration_label' => ['es' => '4 días / 3 noches'],
                'difficulty' => TourDifficulty::Dificil,
                'meeting_point' => ['es' => 'Plaza de Armas, Cusco (punto de muestra)'],
                'inclusions' => ['es' => ['Guía profesional (muestra)', 'Alimentación (muestra)']],
                'exclusions' => ['es' => ['Propinas (muestra)']],
                'price_pen_cents' => 350000,
                'price_usd_cents' => 9500,
                'is_featured' => true,
                'is_published' => true,
                'order' => 1,
                'meta_title' => ['es' => '[MUESTRA] Camino Inca 4 días | Pacha Viva'],
                'meta_description' => ['es' => 'Ficha de muestra, contenido pendiente de la clienta.'],
            ]);
        $tourOne->experiences()->syncWithoutDetaching([$trekking->id, $cultura->id]);

        $tourTwo = Tour::query()->where('slug->es', 'muestra-tour-gastronomico-arequipa')->first()
            ?? Tour::create([
                'destination_id' => $arequipa->id,
                'title' => ['es' => '[MUESTRA] Tour gastronómico en Arequipa'],
                'slug' => ['es' => 'muestra-tour-gastronomico-arequipa'],
                'summary' => ['es' => 'Tour de muestra: ruta de picanterías tradicionales.'],
                'description' => ['es' => 'Contenido de muestra para probar el catálogo. Este texto NO fue provisto por la clienta.'],
                'duration_label' => ['es' => '4 horas'],
                'difficulty' => TourDifficulty::Facil,
                'meeting_point' => ['es' => 'Plaza de Armas, Arequipa (punto de muestra)'],
                'inclusions' => ['es' => ['Degustación (muestra)']],
                'exclusions' => ['es' => ['Bebidas alcohólicas (muestra)']],
                'price_pen_cents' => 12000,
                'price_usd_cents' => 3200,
                'is_featured' => false,
                'is_published' => true,
                'order' => 2,
                'meta_title' => ['es' => '[MUESTRA] Tour gastronómico en Arequipa | Pacha Viva'],
                'meta_description' => ['es' => 'Ficha de muestra, contenido pendiente de la clienta.'],
            ]);
        $tourTwo->experiences()->syncWithoutDetaching([$gastronomia->id]);
    }
}
