<?php

namespace App\Support;

/**
 * Fuente ÚNICA de datos de ejemplo para las 6 pantallas públicas del lote 3
 * (catálogo y ficha de tours/destinos/experiencias). MAQUETA — decisión de
 * Anyerson de mantener este lote como maquetación pura: esta clase NUNCA
 * consulta la base de datos ni los modelos reales (Tour/Destination/
 * Experience ya existen desde el lote 2), aunque reutiliza sus mismos
 * nombres de campo a propósito para que el contrato de datos calce.
 *
 * El backend real sustituye cada método público de esta clase por una
 * consulta Eloquent que devuelva la MISMA forma (array). Ver el contrato de
 * datos invertido en el reporte del lote 3 para el detalle exacto que
 * espera cada vista.
 *
 * Los nombres y descripciones de tours/destinos/experiencias de abajo son
 * contenido de MUESTRA para poder maquetar y verificar el layout — en
 * producción ese contenido lo escribe la clienta desde el CMS
 * (Tour::title, Destination::name, etc. ya son columnas traducibles
 * reales). Por eso las 6 vistas que consumen esta clase se sirven con
 * noindex hasta que el backend las conecte a datos reales.
 */
final class CatalogFixtures
{
    public static function destinations(): array
    {
        return [
            [
                'slug' => 'cusco',
                'name' => 'Cusco',
                'description' => 'La antigua capital del imperio inca, cuna de Machu Picchu y el Valle Sagrado.',
                'cover_image_alt' => 'Vista panorámica de la ciudad de Cusco al atardecer',
                'gallery' => self::gallery('Cusco', '1b6949', 4),
            ],
            [
                'slug' => 'valle-sagrado',
                'name' => 'Valle Sagrado',
                'description' => 'Andenes, mercados tradicionales y pueblos incas entre Pisac y Ollantaytambo.',
                'cover_image_alt' => 'Andenes agrícolas incas en el Valle Sagrado',
                'gallery' => self::gallery('Valle Sagrado', '2c6fa8', 3),
            ],
            [
                'slug' => 'arequipa',
                'name' => 'Arequipa',
                'description' => 'La Ciudad Blanca, puerta de entrada al Cañón del Colca y su cocina tradicional.',
                'cover_image_alt' => 'Plaza de Armas de Arequipa con el volcán Misti de fondo',
                'gallery' => self::gallery('Arequipa', '93590c', 3),
            ],
        ];
    }

    public static function destination(string $slug): ?array
    {
        foreach (self::destinations() as $destination) {
            if ($destination['slug'] === $slug) {
                return $destination;
            }
        }

        return null;
    }

    public static function experiences(): array
    {
        return [
            [
                'slug' => 'trekking',
                'name' => 'Trekking',
                'description' => 'Caminatas guiadas por senderos andinos, de uno o varios días.',
                'cover_image_alt' => 'Grupo de viajeros haciendo trekking en la cordillera andina',
                'gallery' => self::gallery('Trekking', '135338', 3),
            ],
            [
                'slug' => 'cultura',
                'name' => 'Cultura',
                'description' => 'Historia, arqueología y tradiciones vivas del Perú.',
                'cover_image_alt' => 'Viajeros recorriendo ruinas incas con un guía local',
                'gallery' => self::gallery('Cultura', '3d4a42', 3),
            ],
            [
                'slug' => 'gastronomia',
                'name' => 'Gastronomía',
                'description' => 'Sabores y mercados tradicionales de la cocina peruana.',
                'cover_image_alt' => 'Plato tradicional peruano recién servido',
                'gallery' => self::gallery('Gastronomía', 'c2410c', 3),
            ],
        ];
    }

    public static function experience(string $slug): ?array
    {
        foreach (self::experiences() as $experience) {
            if ($experience['slug'] === $slug) {
                return $experience;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function tours(): array
    {
        $tours = [
            [
                'slug' => 'camino-inca-corto',
                'title' => 'Camino Inca Corto a Machu Picchu',
                'summary' => 'Dos días de caminata por el tramo final del Camino Inca hasta llegar a Machu Picchu por la Puerta del Sol.',
                'description' => 'Recorre el tramo final del Camino Inca original, entre bosque nuboso y ruinas incas, y llega a Machu Picchu por la Puerta del Sol al amanecer. Incluye una noche en Aguas Calientes y el ingreso a la ciudadela con guía especializado.',
                'duration_label' => '2 días / 1 noche',
                'difficulty' => 'moderado',
                'destination_slug' => 'cusco',
                'experience_slugs' => ['trekking', 'cultura'],
                'meeting_point' => 'Plaza de Armas de Cusco, 5:00 a.m.',
                'inclusions' => ['Transporte terrestre y en tren', 'Guía profesional bilingüe', 'Entrada a Machu Picchu', 'Una noche de hotel en Aguas Calientes', 'Desayunos incluidos'],
                'exclusions' => ['Alimentación no especificada', 'Propinas', 'Entrada a Huayna Picchu (sujeta a disponibilidad)'],
                'itinerary' => [
                    ['title' => 'Día 1: Cusco - Km 104 - Wiñay Wayna', 'description' => 'Salida temprano en tren hacia el Km 104, inicio de la caminata por el tramo final del Camino Inca hasta el complejo arqueológico de Wiñay Wayna, donde pernoctamos.'],
                    ['title' => 'Día 2: Puerta del Sol - Machu Picchu - Cusco', 'description' => 'Caminata previa al amanecer hasta la Puerta del Sol (Inti Punku) para la primera vista de Machu Picchu, seguida de un recorrido guiado por la ciudadela y retorno a Cusco en tren y bus.'],
                ],
                'price_pen_cents' => 89000,
                'price_usd_cents' => 24000,
                'image_hex' => '1b6949',
            ],
            [
                'slug' => 'machu-picchu-full-day',
                'title' => 'Machu Picchu Full Day',
                'summary' => 'Visita la ciudadela de Machu Picchu en un solo día, ida y vuelta desde Cusco en tren panorámico.',
                'description' => 'Un día completo para conocer Machu Picchu sin trekking: viaje en tren panorámico desde Cusco u Ollantaytambo, bus de subida a la ciudadela y recorrido guiado por sus principales sectores.',
                'duration_label' => '1 día',
                'difficulty' => 'facil',
                'destination_slug' => 'cusco',
                'experience_slugs' => ['cultura'],
                'meeting_point' => 'Hotel en Cusco (recojo incluido), 4:30 a.m.',
                'inclusions' => ['Transporte terrestre a Ollantaytambo', 'Tren panorámico ida y vuelta', 'Bus Aguas Calientes - Machu Picchu', 'Entrada a la ciudadela', 'Guía profesional bilingüe'],
                'exclusions' => ['Alimentación', 'Propinas', 'Entrada a Huayna Picchu o Montaña Machu Picchu'],
                'itinerary' => [
                    ['title' => 'Recojo y viaje en tren', 'description' => 'Recojo en tu hotel de Cusco y traslado a Ollantaytambo para tomar el tren panorámico hacia Aguas Calientes.'],
                    ['title' => 'Machu Picchu y retorno', 'description' => 'Subida en bus a la ciudadela, recorrido guiado de aproximadamente 2 horas, tiempo libre y retorno a Cusco el mismo día.'],
                ],
                'price_pen_cents' => 75000,
                'price_usd_cents' => 20000,
                'image_hex' => '2c6fa8',
            ],
            [
                'slug' => 'tour-valle-sagrado',
                'title' => 'Tour Valle Sagrado',
                'summary' => 'Recorre Pisac, Ollantaytambo y sus mercados tradicionales en un día completo.',
                'description' => 'Un recorrido de día completo por los principales pueblos y sitios arqueológicos del Valle Sagrado de los Incas, con paradas en mercados artesanales y andenes agrícolas todavía en uso.',
                'duration_label' => '1 día',
                'difficulty' => 'facil',
                'destination_slug' => 'valle-sagrado',
                'experience_slugs' => ['cultura'],
                'meeting_point' => 'Hotel en Cusco (recojo incluido), 8:00 a.m.',
                'inclusions' => ['Transporte turístico', 'Guía profesional bilingüe', 'Entradas a los sitios arqueológicos'],
                'exclusions' => ['Almuerzo', 'Propinas'],
                'itinerary' => [
                    ['title' => 'Pisac', 'description' => 'Visita al mercado artesanal y a los andenes agrícolas incas de Pisac.'],
                    ['title' => 'Ollantaytambo', 'description' => 'Recorrido por la fortaleza y el pueblo inca de Ollantaytambo, uno de los mejor conservados del Perú.'],
                ],
                'price_pen_cents' => 45000,
                'price_usd_cents' => 12000,
                'image_hex' => '2c6fa8',
            ],
            [
                'slug' => 'montana-de-7-colores',
                'title' => 'Montaña de 7 Colores',
                'summary' => 'Caminata de un día hasta el mirador de Vinicunca, la famosa montaña arcoíris.',
                'description' => 'Caminata de altura hasta el mirador de Vinicunca, conocida como la Montaña de 7 Colores por las franjas minerales de su ladera. Ruta exigente por la altitud, con paisajes andinos de altura durante todo el recorrido.',
                'duration_label' => '1 día',
                'difficulty' => 'dificil',
                'destination_slug' => 'cusco',
                'experience_slugs' => ['trekking'],
                'meeting_point' => 'Plaza de Armas de Cusco, 3:30 a.m.',
                'inclusions' => ['Transporte terrestre', 'Guía profesional', 'Desayuno y almuerzo'],
                'exclusions' => ['Bastones de trekking', 'Propinas', 'Entrada a la montaña'],
                'itinerary' => [
                    ['title' => 'Ascenso a Vinicunca', 'description' => 'Salida muy temprano hacia Cusipata, inicio de la caminata de ascenso (unas 3 horas) hasta el mirador principal de la Montaña de 7 Colores.'],
                    ['title' => 'Descenso y retorno', 'description' => 'Tiempo libre en el mirador, descenso al punto de partida y retorno a Cusco.'],
                ],
                'price_pen_cents' => 55000,
                'price_usd_cents' => 15000,
                'image_hex' => '93590c',
            ],
            [
                'slug' => 'salkantay-trek',
                'title' => 'Trek de Salkantay a Machu Picchu',
                'summary' => 'Cinco días de trekking por la ruta alternativa del nevado Salkantay hasta Machu Picchu.',
                'description' => 'Una alternativa al Camino Inca clásico: cinco días de caminata a través de paisajes de nevados, selva alta y plantaciones de café, terminando con la visita a Machu Picchu.',
                'duration_label' => '5 días / 4 noches',
                'difficulty' => 'dificil',
                'destination_slug' => 'cusco',
                'experience_slugs' => ['trekking'],
                'meeting_point' => 'Hotel en Cusco (recojo incluido), 4:00 a.m.',
                'inclusions' => ['Transporte terrestre', 'Guía profesional bilingüe', 'Equipo de camping', 'Alimentación completa durante el trek', 'Entrada a Machu Picchu', 'Tren de retorno a Cusco'],
                'exclusions' => ['Saco de dormir', 'Propinas', 'Entrada a Huayna Picchu'],
                'itinerary' => [
                    ['title' => 'Día 1: Cusco - Soraypampa - Soyrococha', 'description' => 'Inicio de la caminata hacia el campamento base del nevado Salkantay.'],
                    ['title' => 'Día 2: Paso de Salkantay - Chaullay', 'description' => 'Cruce del paso más alto de la ruta (4,600 msnm) y descenso hacia zonas de selva alta.'],
                    ['title' => 'Día 3: Chaullay - La Playa - Lucmabamba', 'description' => 'Caminata entre plantaciones de café y frutales tropicales.'],
                    ['title' => 'Día 4: Lucmabamba - Hidroeléctrica - Aguas Calientes', 'description' => 'Cruce de montaña hasta la Hidroeléctrica y caminata final junto a las vías del tren hasta Aguas Calientes.'],
                    ['title' => 'Día 5: Machu Picchu - Cusco', 'description' => 'Visita guiada a Machu Picchu al amanecer y retorno a Cusco en tren y bus.'],
                ],
                'price_pen_cents' => 165000,
                'price_usd_cents' => 44000,
                'image_hex' => '135338',
            ],
            [
                'slug' => 'ruta-gastronomica-arequipa',
                'title' => 'Ruta Gastronómica por Arequipa',
                'summary' => 'Recorre picanterías tradicionales y el mercado San Camilo probando los platos típicos de Arequipa.',
                'description' => 'Un recorrido de medio día por el mercado San Camilo y picanterías tradicionales del centro de Arequipa, probando platos representativos de la cocina arequipeña de la mano de un guía local.',
                'duration_label' => 'Medio día',
                'difficulty' => 'facil',
                'destination_slug' => 'arequipa',
                'experience_slugs' => ['gastronomia'],
                'meeting_point' => 'Plaza de Armas de Arequipa, 10:00 a.m.',
                'inclusions' => ['Guía profesional bilingüe', 'Degustaciones incluidas en el recorrido', 'Agua embotellada'],
                'exclusions' => ['Bebidas alcohólicas', 'Propinas'],
                'itinerary' => [
                    ['title' => 'Mercado San Camilo', 'description' => 'Recorrido por los puestos de frutas, quesos y comida preparada del mercado más tradicional de Arequipa.'],
                    ['title' => 'Picanterías del centro', 'description' => 'Visita a dos picanterías tradicionales para probar platos representativos de la cocina arequipeña.'],
                ],
                'price_pen_cents' => 35000,
                'price_usd_cents' => 9500,
                'image_hex' => 'c2410c',
            ],
            [
                'slug' => 'canon-del-colca',
                'title' => 'Cañón del Colca 2 días',
                'summary' => 'Dos días recorriendo uno de los cañones más profundos del mundo, con avistamiento de cóndores.',
                'description' => 'Dos días de viaje desde Arequipa hasta el Cañón del Colca, con parada en la Cruz del Cóndor para observar el vuelo de los cóndores andinos y una caminata corta por el borde del cañón.',
                'duration_label' => '2 días / 1 noche',
                'difficulty' => 'moderado',
                'destination_slug' => 'arequipa',
                'experience_slugs' => ['trekking'],
                'meeting_point' => 'Hotel en Arequipa (recojo incluido), 8:00 a.m.',
                'inclusions' => ['Transporte turístico', 'Guía profesional bilingüe', 'Una noche de hotel en el valle del Colca', 'Desayuno incluido'],
                'exclusions' => ['Almuerzos y cenas', 'Entrada al valle del Colca', 'Propinas'],
                'itinerary' => [
                    ['title' => 'Día 1: Arequipa - Valle del Colca', 'description' => 'Viaje por altiplano hasta el valle del Colca, con paradas en miradores y pueblos tradicionales.'],
                    ['title' => 'Día 2: Cruz del Cóndor - Arequipa', 'description' => 'Observación del vuelo de los cóndores en la Cruz del Cóndor, caminata corta por el borde del cañón y retorno a Arequipa.'],
                ],
                'price_pen_cents' => 68000,
                'price_usd_cents' => 18500,
                'image_hex' => '93590c',
            ],
        ];

        return array_map(function (array $tour): array {
            $destination = self::destination($tour['destination_slug']);
            $experiences = array_values(array_filter(array_map(
                fn (string $slug) => self::experience($slug),
                $tour['experience_slugs']
            )));

            $tour['destination'] = $destination ? ['slug' => $destination['slug'], 'name' => $destination['name']] : null;
            $tour['experiences'] = array_map(
                fn (array $experience) => ['slug' => $experience['slug'], 'name' => $experience['name']],
                $experiences
            );
            $tour['images'] = self::gallery($tour['title'], $tour['image_hex'], 4);

            unset($tour['destination_slug'], $tour['experience_slugs'], $tour['image_hex']);

            return $tour;
        }, $tours);
    }

    public static function tour(string $slug): ?array
    {
        foreach (self::tours() as $tour) {
            if ($tour['slug'] === $slug) {
                return $tour;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function toursByDestination(string $destinationSlug): array
    {
        return array_values(array_filter(
            self::tours(),
            fn (array $tour) => ($tour['destination']['slug'] ?? null) === $destinationSlug
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function toursByExperience(string $experienceSlug): array
    {
        return array_values(array_filter(
            self::tours(),
            fn (array $tour) => collect($tour['experiences'])->contains('slug', $experienceSlug)
        ));
    }

    /**
     * Galería de fotos placeholder (misma técnica que PlaceholderImage ya usa
     * en el resto del sitio): sin fotos reales todavía, cada tour/destino/
     * experiencia siempre trae al menos una imagen — nunca una galería
     * vacía por diseño de esta clase.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    private static function gallery(string $label, string $hex, int $count): array
    {
        return array_map(
            fn (int $i) => [
                'src' => PlaceholderImage::svg(1200, 800, $label.' ('.($i + 1).')', $hex),
                'alt' => $label.' — foto '.($i + 1),
            ],
            range(0, $count - 1)
        );
    }
}
