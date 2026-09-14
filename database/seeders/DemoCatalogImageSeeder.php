<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * TEMPORARY STOCK PHOTOS. Wires the Unsplash photo bank in
 * resources/images/demo-catalog/ (commercial-use license, see CREDITOS.md
 * in that same folder) onto the 3 sample tours / 3 sample destinations / 3
 * sample experiences that DemoTourSeeder already creates -- purely so the
 * catalog stops rendering PlaceholderImage::svg() color blocks everywhere
 * while the client's own photography is pending. Once real photos replace
 * this seeder's output, this class and resources/images/demo-catalog/ can
 * both be deleted; nothing else in the app depends on either.
 *
 * Assumes DemoTourSeeder has already run in the same batch (see
 * DatabaseSeeder::run()) and looks records up by their known "es" slug --
 * never creates a Tour/Destination/Experience itself, and silently does
 * nothing for any record it can't find (defensive: this seeder is not the
 * source of truth for the catalog, DemoTourSeeder is).
 *
 * Idempotent: `Storage::put()` overwrites the same deterministic path on
 * every run, and every gallery row is updateOrCreate()'d by its natural key
 * (parent id + path) -- running `migrate:fresh --seed` twice in a row never
 * duplicates a gallery or leaves two rows pointing at the same file.
 */
class DemoCatalogImageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDestinations();
        $this->seedExperiences();
        $this->seedTours();
    }

    private function seedDestinations(): void
    {
        $this->wireDestination('cusco', 'destino-cusco.jpg', [
            'es' => 'Plaza de Armas y catedral del Cusco vistas desde lo alto',
            'en' => "Cusco's Plaza de Armas and cathedral seen from above",
        ], [
            ['destino-cusco-galeria-01.jpg', 'Calle empedrada y empinada entre casas de tejas ocre, con la ciudad del Cusco al fondo', 'Steep cobblestone street lined with ochre-roofed houses, overlooking the city of Cusco'],
            ['destino-cusco-galeria-02.jpg', 'Fachada de piedra y sillar de la Catedral del Cusco', 'Stone and sillar facade of Cusco Cathedral'],
            ['destino-cusco-galeria-03.jpg', 'Plaza de Armas del Cusco iluminada de noche, con gente paseando', "Cusco's Plaza de Armas lit up at night, with people strolling"],
        ]);

        $this->wireDestination('valle-sagrado', 'destino-valle-sagrado.jpg', [
            'es' => 'Vista panorámica de los andenes incas de Pisac en el Valle Sagrado',
            'en' => 'Panoramic view of the Inca terraces of Pisac in the Sacred Valley',
        ], [
            ['destino-valle-sagrado-galeria-01.jpg', 'Pueblo de Ollantaytambo con las colcas incas en la ladera del cerro', 'The town of Ollantaytambo with Inca storehouses (colcas) on the hillside'],
            ['destino-valle-sagrado-galeria-02.jpg', 'Monolitos de piedra del Templo del Sol en Ollantaytambo', 'Stone monoliths of the Temple of the Sun in Ollantaytambo'],
            ['destino-valle-sagrado-galeria-03.jpg', 'Río Urubamba serpenteando entre las chacras del Valle Sagrado', 'The Urubamba River winding through the farmland of the Sacred Valley'],
        ]);

        $this->wireDestination('arequipa', 'destino-arequipa.jpg', [
            'es' => 'Vista aérea de la ciudad de Arequipa con el volcán Misti al fondo',
            'en' => 'Aerial view of the city of Arequipa with the Misti volcano in the background',
        ], [
            ['destino-arequipa-galeria-01.jpg', 'Arcos de piedra roja en un patio del Monasterio de Santa Catalina, Arequipa', 'Red stone arches in a courtyard of the Santa Catalina Monastery, Arequipa'],
            ['destino-arequipa-galeria-02.jpg', 'El volcán Misti nevado sobre los andenes y la campiña de Arequipa', 'The snow-capped Misti volcano above the terraces and countryside of Arequipa'],
            ['destino-arequipa-galeria-03.jpg', 'Portales de sillar blanco junto a la Basílica Catedral de Arequipa', "White sillar-stone arcades next to Arequipa's Cathedral Basilica"],
            ['destino-arequipa-galeria-04.jpg', 'El volcán Misti visto a través de uno de los arcos de sillar tallado del mirador de Yanahuara, Arequipa', 'The Misti volcano framed by one of the carved sillar-stone arches at the Yanahuara viewpoint, Arequipa'],
            ['destino-arequipa-galeria-05.jpg', 'El volcán Misti sobre la ciudad de Arequipa al atardecer, con una persona como referencia de escala', 'The Misti volcano over the city of Arequipa at sunset, with a person for scale'],
            ['destino-arequipa-galeria-06.jpg', 'Fachada tallada en sillar blanco en las canteras de Añashuayco, Arequipa', 'Facade carved into white sillar stone at the Añashuayco quarries, Arequipa'],
        ]);
    }

    private function seedExperiences(): void
    {
        $this->wireExperience('trekking', 'experiencia-trekking.jpg', [
            'es' => 'Caminantes de trekking en una ruta andina rumbo a los nevados',
            'en' => 'Trekkers on an Andean trail heading toward the snow-capped peaks',
        ], [
            ['experiencia-trekking-vinicunca.jpg', 'Caminantes ascendiendo la Montaña de Siete Colores (Vinicunca)', 'Hikers climbing the Rainbow Mountain (Vinicunca)'],
        ]);

        // No dedicated EXTRA gallery photo for this experience in the bank
        // (only the cover shot) -- unlike Tour, experiences/index.blade.php
        // and experiences/show.blade.php's gallery viewer both read
        // gallery()[0], never cover_image_path directly (see the open note
        // in this lote's report). Without at least one gallery row,
        // Gastronomía would be the only experience still showing
        // PlaceholderImage::svg() on /experiencias and on its own ficha,
        // while Trekking/Cultura show real photos -- a visible inconsistency
        // caused by this seeder, not a pre-existing one. Reusing the same
        // licensed cover photo as gallery[0] fixes it with data only, no
        // fabricated second photo, no view change.
        $this->wireExperience('gastronomia', 'experiencia-gastronomia.jpg', [
            'es' => 'Variedad de maíces nativos peruanos de distintos colores',
            'en' => 'A variety of native Peruvian corn in different colors',
        ], [
            ['experiencia-gastronomia.jpg', 'Variedad de maíces nativos peruanos de distintos colores', 'A variety of native Peruvian corn in different colors'],
        ]);

        $this->wireExperience('cultura', 'experiencia-cultura.jpg', [
            'es' => 'Tejedora andina trabajando en un telar de cintura tradicional',
            'en' => 'An Andean weaver working on a traditional backstrap loom',
        ], [
            ['experiencia-cultura-hilanderas.jpg', 'Mujeres de Chinchero hilando lana de forma tradicional', 'Women from Chinchero spinning wool in the traditional way'],
            ['experiencia-cultura-retrato.jpg', 'Retrato de una mujer andina con montera tradicional', 'Portrait of an Andean woman wearing a traditional montera hat'],
            ['experiencia-cultura-comunidad.jpg', 'Integrantes de una comunidad andina frente a una casa de adobe', 'Members of an Andean community in front of an adobe house'],
            ['experiencia-cultura-textiles-mercado.jpg', 'Puesto de venta de textiles tradicionales en el mercado de Pisac', 'A stall selling traditional textiles at the Pisac market'],
        ]);
    }

    private function seedTours(): void
    {
        // tour-camino-inca-*: the bank has no porter/campsite photos for this
        // tour (see CREDITOS.md) -- none of the alt text below claims either.
        $this->wireTour('muestra-camino-inca-4-dias', 'tour-camino-inca.jpg', [
            'es' => 'Caminante recorriendo el sendero del Camino Inca',
            'en' => 'A hiker walking the Inca Trail',
        ], [
            ['tour-camino-inca-galeria-01.jpg', 'Tramo empedrado original del Camino Inca al atardecer', 'An original stone-paved section of the Inca Trail at sunset'],
            ['tour-camino-inca-galeria-02.jpg', 'Ruinas de Wiñay Wayna sobre sus andenes escalonados', 'The ruins of Wiñay Wayna above its stepped terraces'],
            ['tour-camino-inca-galeria-03.jpg', 'Llegada a Machu Picchu con la luz dorada del amanecer', 'Arrival at Machu Picchu bathed in golden morning light'],
            ['tour-camino-inca-galeria-04.jpg', 'Las ruinas circulares de Runkurakay entre las nubes', 'The circular ruins of Runkurakay among the clouds'],
        ]);

        $this->wireTour('muestra-tour-gastronomico-arequipa', 'tour-gastronomia-arequipa.jpg', [
            'es' => 'Anticuchos, choclo y mariscos a la parrilla, platos típicos de la gastronomía peruana',
            'en' => 'Anticuchos, corn, and grilled seafood, typical dishes of Peruvian cuisine',
        ], [
            // NOT confirmed this market photo is in Arequipa (see
            // CREDITOS.md) -- alt deliberately has no city name, ES or EN.
            ['tour-gastronomia-arequipa-galeria-01.jpg', 'Cocineras trabajando en un mercado peruano', 'Cooks at work in a Peruvian market'],
            ['tour-gastronomia-arequipa-galeria-02.jpg', 'Plato de ceviche peruano con camote, choclo y chicharrón', 'Peruvian ceviche served with sweet potato, corn, and chicharrón'],
            ['tour-gastronomia-arequipa-galeria-03.jpg', 'Puesto de quesos artesanales en un mercado', 'A stall selling artisanal cheeses at a market'],
            ['tour-gastronomia-arequipa-galeria-04.jpg', 'Papas andinas servidas con queso y salsa de huacatay', 'Andean potatoes served with cheese and huacatay sauce'],
        ]);

        $this->wireTour('muestra-tour-valle-sagrado', 'tour-valle-sagrado.jpg', [
            'es' => 'Andenes circulares de Moray, en el Valle Sagrado',
            'en' => 'The circular terraces of Moray, in the Sacred Valley',
        ], [
            ['tour-valle-sagrado-galeria-01.jpg', 'Salineras de Maras dispuestas en terrazas sobre la quebrada', 'The Maras salt pans arranged in terraces above the ravine'],
            ['tour-valle-sagrado-galeria-02.jpg', 'Detalle de las terrazas circulares de Moray', 'A close-up of the circular terraces of Moray'],
            ['tour-valle-sagrado-galeria-03.jpg', 'El pueblo de Ollantaytambo rodeado de montañas', 'The town of Ollantaytambo surrounded by mountains'],
        ]);
    }

    /**
     * @param  array{es: string, en: string}  $coverAlt
     * @param  list<array{0: string, 1: string, 2: string}>  $gallery
     */
    private function wireDestination(string $slug, string $coverFile, array $coverAlt, array $gallery): void
    {
        $destination = Destination::query()->where('slug->es', $slug)->first();

        if ($destination === null) {
            return;
        }

        $destination->update([
            'cover_image_path' => $this->copyToPublicDisk($coverFile, 'destinations'),
            'cover_image_alt' => $coverAlt,
        ]);

        $this->wireGallery($destination->gallery(), 'destinations', $gallery);
    }

    /**
     * @param  array{es: string, en: string}  $coverAlt
     * @param  list<array{0: string, 1: string, 2: string}>  $gallery
     */
    private function wireExperience(string $slug, string $coverFile, array $coverAlt, array $gallery): void
    {
        $experience = Experience::query()->where('slug->es', $slug)->first();

        if ($experience === null) {
            return;
        }

        $experience->update([
            'cover_image_path' => $this->copyToPublicDisk($coverFile, 'experiences'),
            'cover_image_alt' => $coverAlt,
        ]);

        $this->wireGallery($experience->gallery(), 'experiences', $gallery);
    }

    /**
     * Tour has no cover_image_path column (see App\Models\Tour and
     * docs/lote-4): its "cover" is simply images()->first() (order 1) --
     * cover and gallery are ONE ordered list here, unlike Destination/
     * Experience, which keep the cover in its own dedicated column.
     *
     * @param  array{es: string, en: string}  $coverAlt
     * @param  list<array{0: string, 1: string, 2: string}>  $gallery
     */
    private function wireTour(string $slug, string $coverFile, array $coverAlt, array $gallery): void
    {
        $tour = Tour::query()->where('slug->es', $slug)->first();

        if ($tour === null) {
            return;
        }

        $ordered = [[$coverFile, $coverAlt['es'], $coverAlt['en']], ...$gallery];

        $this->wireGallery($tour->images(), 'tours', $ordered);
    }

    /**
     * @param  HasMany<covariant \Illuminate\Database\Eloquent\Model, *>  $relation
     * @param  list<array{0: string, 1: string, 2: string}>  $items
     */
    private function wireGallery(HasMany $relation, string $directory, array $items): void
    {
        foreach ($items as $i => [$file, $altEs, $altEn]) {
            $path = $this->copyToPublicDisk($file, $directory);

            $relation->updateOrCreate(
                ['path' => $path],
                ['alt' => ['es' => $altEs, 'en' => $altEn], 'order' => $i + 1]
            );
        }
    }

    /**
     * Copies one photo from the version-controlled bank
     * (resources/images/demo-catalog/) onto the "public" disk, alongside its
     * WebP sibling if one exists -- same directory, same basename, only the
     * extension differs. Only the returned JPG path is written to any DB
     * column: the current Blade templates render a single <img src>, no
     * <picture>/srcset, so wiring a second "path_webp" column would be
     * schema nobody reads yet. The WebP file is staged on disk regardless,
     * per the brief ("copia las dos versiones"), ready for whoever adds
     * that markup later without needing new data.
     */
    private function copyToPublicDisk(string $filename, string $directory): string
    {
        $source = resource_path("images/demo-catalog/{$filename}");
        $target = "{$directory}/{$filename}";

        Storage::disk('public')->put($target, file_get_contents($source));

        $webpFilename = Str::replaceLast('.jpg', '.webp', $filename);
        $webpSource = resource_path("images/demo-catalog/webp/{$webpFilename}");

        if (is_file($webpSource)) {
            Storage::disk('public')->put("{$directory}/{$webpFilename}", file_get_contents($webpSource));
        }

        return $target;
    }
}
