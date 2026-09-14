<?php

namespace Database\Seeders;

use App\Enums\TourDifficulty;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\Tour;
use Illuminate\Database\Seeder;

/**
 * TEMPORARY DEMO CATALOG. Fills the 3 tours / 3 destinations / 3
 * experiences with plausible, readable ES+EN copy so the finished layout
 * can be judged on its design instead of on "[MUESTRA] Tour Valle Sagrado"
 * placeholders. NONE of this text came from the client.
 *
 * Every invented figure (prices, duration labels, difficulty) is listed
 * record by record in docs/lote-3/contenido-demo-2026-09-14.md, which is
 * the artifact the client has to sign off on before any of this reaches
 * production. The prose here deliberately carries NO invented measurement:
 * no altitudes, no distances, no group sizes, no departure times, and no
 * "X years of experience" style credibility claims (project rule 3, see
 * .claude/proyecto/00-contexto.md). Place names, archaeological sites and
 * dish names are public geography/heritage, not fabricated claims.
 *
 * The safety net that keeps this off Google is config('cms.catalog_demo_content'),
 * which is still `true`: every catalog page (index + ficha, both locales)
 * renders `noindex` regardless of how real the copy now reads. Do NOT flip
 * that flag as part of a content change — it is the switch the client's own
 * data flips, and it is the reason it is safe to write believable demo copy
 * at all. `php artisan data:audit-sample` now reports all 9 catalog rows as
 * "sin prefijo [MUESTRA]", which is correct and intended: the prefix is
 * gone, so the audit trail moved to the doc named above.
 *
 * IDEMPOTENT by construction: every record is looked up by its "es" slug
 * (falling back to the pre-rename slug used by the previous version of this
 * seeder, so an existing database migrates instead of growing duplicates),
 * then fill()+save()'d with the exact same payload. A second run finds the
 * model non-dirty and writes nothing — in particular it records no second
 * TourSlugHistory row, because the slug only differs on the first pass.
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
        $cusco = $this->upsertDestination('cusco', [
            'name' => ['es' => 'Cusco', 'en' => 'Cusco'],
            'slug' => ['es' => 'cusco', 'en' => 'cusco'],
            'description' => [
                'es' => 'Capital del Tahuantinsuyo y puerta de entrada a Machu Picchu. Cusco se camina despacio: muros incas que todavía sostienen balcones coloniales, barrios empinados como San Blas, mercados donde se sigue regateando en quechua y una Plaza de Armas que no se parece en nada a sí misma entre el mediodía y la noche. Es la base natural para cualquier ruta por los Andes del sur.',
                'en' => 'The old Inca capital, and the gateway to Machu Picchu. Cusco rewards a slow pace: Inca walls still holding up colonial balconies, steep neighbourhoods like San Blas, markets where the haggling happens in Quechua, and a main square that feels like two different places at noon and after dark. It is the natural base for any journey through the southern Andes.',
            ],
            'meta_title' => [
                'es' => 'Viajes y tours en Cusco | Pacha Viva',
                'en' => 'Cusco Travel and Tours | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Qué ver y qué hacer en Cusco: centro histórico, San Blas, mercados y las rutas que salen de la ciudad hacia el Valle Sagrado y Machu Picchu.',
                'en' => 'What to see and do in Cusco: the historic centre, San Blas, the markets, and the routes that leave the city for the Sacred Valley and Machu Picchu.',
            ],
            'is_published' => true,
            'order' => 1,
        ]);

        $valleSagrado = $this->upsertDestination('valle-sagrado', [
            'name' => ['es' => 'Valle Sagrado', 'en' => 'Sacred Valley'],
            'slug' => ['es' => 'valle-sagrado', 'en' => 'sacred-valley'],
            'description' => [
                'es' => 'El valle que el río Urubamba abrió entre Cusco y Machu Picchu. Está más bajo y más templado que la ciudad, así que es el mejor sitio para aclimatarse antes de subir a cualquier ruta de altura. Aquí están los andenes de Pisac, las pozas de sal de Maras, los círculos de Moray y Ollantaytambo, el único pueblo del valle que se sigue habitando sobre su trazado inca original.',
                'en' => 'The valley the Urubamba river carved between Cusco and Machu Picchu. It sits lower and warmer than the city, which makes it the best place to acclimatise before taking on anything at altitude. This is where you find the terraces of Pisac, the salt pans of Maras, the circles of Moray, and Ollantaytambo, the only town in the valley still lived in on its original Inca street plan.',
            ],
            'meta_title' => [
                'es' => 'Valle Sagrado de los Incas: tours y rutas | Pacha Viva',
                'en' => 'The Sacred Valley of the Incas: Tours and Routes | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Pisac, Maras, Moray y Ollantaytambo en el valle del Urubamba, la salida que mejor funciona para aclimatarse antes de una ruta de altura.',
                'en' => 'Pisac, Maras, Moray and Ollantaytambo in the Urubamba valley — the day out that works best for acclimatising before a high-altitude route.',
            ],
            'is_published' => true,
            'order' => 2,
        ]);

        $arequipa = $this->upsertDestination('arequipa', [
            'name' => ['es' => 'Arequipa', 'en' => 'Arequipa'],
            'slug' => ['es' => 'arequipa', 'en' => 'arequipa'],
            'description' => [
                'es' => 'La ciudad blanca, levantada en sillar volcánico al pie del Misti. Arequipa tiene acento propio, cocina propia y un centro histórico que se recorre entero a pie: el monasterio de Santa Catalina, los portales de la plaza, el mirador de Yanahuara y la campiña, que empieza justo donde se acaba el asfalto.',
                'en' => 'The white city, built out of volcanic sillar stone at the foot of El Misti. Arequipa has its own accent, its own kitchen, and a historic centre you can cover entirely on foot: the Santa Catalina monastery, the arcades around the main square, the Yanahuara viewpoint, and the farmland that begins exactly where the pavement ends.',
            ],
            'meta_title' => [
                'es' => 'Arequipa: qué ver y dónde comer | Pacha Viva',
                'en' => 'Arequipa: What to See and Where to Eat | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Santa Catalina, el sillar, el mirador de Yanahuara y las picanterías de siempre: cómo aprovechar Arequipa en pocos días.',
                'en' => 'Santa Catalina, the sillar stone, the Yanahuara viewpoint and the old picanterías: how to make the most of Arequipa in a few days.',
            ],
            'is_published' => true,
            'order' => 3,
        ]);

        $trekking = $this->upsertExperience('trekking', [
            'name' => ['es' => 'Trekking', 'en' => 'Trekking'],
            'slug' => ['es' => 'trekking', 'en' => 'trekking'],
            'description' => [
                'es' => 'Rutas de altura, de una jornada o de varios días, durmiendo en campamento o en casa de pueblo. Caminos de herradura, abras, lagunas y tramos empedrados que siguen en pie desde antes de la Colonia. Cada salida declara su exigencia física en su propia ficha, y ninguna se vende como más fácil de lo que es.',
                'en' => 'High-altitude routes, from a single day to several, sleeping under canvas or in a village guesthouse. Mule tracks, mountain passes, glacial lakes, and stretches of stone path that have been in use since long before the Spanish arrived. Every departure states its physical demand on its own page, and none of them is sold as easier than it is.',
            ],
            'meta_title' => [
                'es' => 'Trekking en los Andes del Perú | Pacha Viva',
                'en' => 'Trekking in the Peruvian Andes | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Caminatas de uno o varios días por los Andes del sur, con guía oficial y exigencia física declarada en cada ruta.',
                'en' => 'One-day and multi-day walks through the southern Andes, with licensed guides and the physical demand stated up front on every route.',
            ],
            'is_published' => true,
            'order' => 1,
        ]);

        $gastronomia = $this->upsertExperience('gastronomia', [
            'name' => ['es' => 'Gastronomía', 'en' => 'Cuisine'],
            'slug' => ['es' => 'gastronomia', 'en' => 'cuisine'],
            'description' => [
                'es' => 'Mercados, picanterías y cocinas de casa. Comer es la forma más rápida de entender una región del Perú: qué se siembra, qué se conserva, qué se celebra y en qué fecha. Rutas pensadas para probar sin prisa, preguntar mucho y volver después por cuenta propia.',
                'en' => 'Markets, picanterías, and home kitchens. Eating is the fastest way to understand a Peruvian region — what it grows, what it preserves, what it celebrates and when. These routes are built for tasting slowly, asking plenty of questions, and going back on your own afterwards.',
            ],
            'meta_title' => [
                'es' => 'Rutas gastronómicas en el Perú | Pacha Viva',
                'en' => 'Peruvian Food Routes | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Mercados, picanterías y cocina regional peruana explicada en la mesa, no en una carta traducida.',
                'en' => 'Markets, picanterías and regional Peruvian cooking explained at the table rather than on a translated menu.',
            ],
            'is_published' => true,
            'order' => 2,
        ]);

        $cultura = $this->upsertExperience('cultura', [
            'name' => ['es' => 'Cultura', 'en' => 'Culture'],
            'slug' => ['es' => 'cultura', 'en' => 'culture'],
            'description' => [
                'es' => 'Sitios arqueológicos, talleres textiles y comunidades donde todavía se hila, se tiñe y se teje como se aprendió en casa. Las visitas van con guía y coordinadas de antemano con la comunidad anfitriona, nunca de sorpresa.',
                'en' => 'Archaeological sites, weaving workshops, and communities where people still spin, dye and weave the way they learned at home. Visits are guided and agreed in advance with the host community — never a surprise arrival.',
            ],
            'meta_title' => [
                'es' => 'Cultura viva y sitios arqueológicos | Pacha Viva',
                'en' => 'Living Culture and Archaeological Sites | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Arqueología inca, textiles andinos y visitas coordinadas con las comunidades que los producen.',
                'en' => 'Inca archaeology, Andean textiles, and visits arranged with the communities that make them.',
            ],
            'is_published' => true,
            'order' => 3,
        ]);

        $this->upsertTour('muestra-camino-inca-4-dias', [
            'destination_id' => $cusco->id,
            'title' => [
                'es' => 'Camino Inca a Machu Picchu, 4 días',
                'en' => 'Inca Trail to Machu Picchu, 4 Days',
            ],
            'slug' => [
                'es' => 'camino-inca-machu-picchu-4-dias',
                'en' => 'inca-trail-machu-picchu-4-days',
            ],
            'summary' => [
                'es' => 'El trekking clásico de los Andes: cuatro días por el empedrado original hasta entrar a Machu Picchu por la Puerta del Sol.',
                'en' => 'The classic Andean trek: four days on original Inca stonework, arriving at Machu Picchu through the Sun Gate.',
            ],
            'description' => [
                'es' => '<p>El Camino Inca no es el atajo a Machu Picchu: es el camino largo, el que los incas construyeron para llegar caminando. Empedrado original, escalinatas talladas en la roca viva, abras por encima de la línea de árboles y bosque de neblina en el descenso.</p>'
                    .'<p>La ruta encadena sitios que no se ven desde ningún otro lado: Runkurakay entre nubes, Sayacmarca sobre su promontorio, Wiñay Wayna colgado de sus andenes. La última mañana se sale de noche para cruzar la Puerta del Sol y ver aparecer la ciudadela entera, antes de que suba el primer bus del valle.</p>'
                    .'<p>Se camina con guía oficial y equipo de porteadores, y el campamento ya está armado al llegar. La exigencia es real: hay subida sostenida y se duerme en altura.</p>',
                'en' => '<p>The Inca Trail is not the shortcut to Machu Picchu. It is the long way round — the one the Incas built so that you would arrive on foot. Original paving, staircases cut into the bedrock, passes above the treeline, and cloud forest on the way down.</p>'
                    .'<p>The route strings together places you cannot reach any other way: Runkurakay in the clouds, Sayacmarca on its outcrop, Wiñay Wayna hanging off its terraces. On the last morning you set out in the dark to cross the Sun Gate and watch the whole citadel come into view, before the first bus has climbed out of the valley.</p>'
                    .'<p>You walk with a licensed guide and a porter team, and camp is already standing when you get there. The effort is real: sustained climbing, and nights spent at altitude.</p>',
            ],
            'duration_label' => ['es' => '4 días / 3 noches', 'en' => '4 days / 3 nights'],
            'difficulty' => TourDifficulty::Dificil,
            'meeting_point' => ['es' => 'Plaza de Armas, Cusco', 'en' => 'Plaza de Armas, Cusco'],
            'inclusions' => [
                'es' => [
                    'Permiso de ingreso al Camino Inca y a Machu Picchu',
                    'Guía oficial de turismo en español o inglés',
                    'Equipo de porteadores y campamento armado',
                    'Alimentación completa durante la ruta',
                    'Retorno a Cusco en tren y bus',
                ],
                'en' => [
                    'Inca Trail and Machu Picchu entrance permits',
                    'Licensed guide, in Spanish or English',
                    'Porter team, with camp set up on arrival',
                    'All meals on the trail',
                    'Return to Cusco by train and bus',
                ],
            ],
            'exclusions' => [
                'es' => [
                    'Primer desayuno y última cena',
                    'Bolsa de dormir y bastones de trekking',
                    'Ingreso a la montaña Huayna Picchu',
                    'Propinas al equipo de porteadores',
                    'Seguro de viaje',
                ],
                'en' => [
                    'First breakfast and final dinner',
                    'Sleeping bag and trekking poles',
                    'Huayna Picchu mountain ticket',
                    'Tips for the porter team',
                    'Travel insurance',
                ],
            ],
            'itinerary' => [
                'es' => [
                    [
                        'title' => 'Día 1 · Del río al primer campamento',
                        'description' => '<p>Traslado desde Cusco hasta el control donde arranca la ruta, junto al Urubamba. La primera jornada es la más amable: sube poco a poco entre chacras y pasa frente a Llactapata, un asentamiento inca que se ve desde el propio sendero. Se acampa en un pueblo del valle.</p>',
                    ],
                    [
                        'title' => 'Día 2 · Abra de Warmiwañusca',
                        'description' => '<p>El día duro. Se sube en firme hasta el punto más alto de la ruta y se baja al valle siguiente. Se camina despacio y con paradas: acá arriba pesa más la altura que la distancia.</p>',
                    ],
                    [
                        'title' => 'Día 3 · Ruinas entre nubes',
                        'description' => '<p>La jornada más bonita. Runkurakay, Sayacmarca y Phuyupatamarca aparecen uno tras otro mientras el paisaje pasa de puna a bosque de neblina. Termina en Wiñay Wayna, sobre sus andenes.</p>',
                    ],
                    [
                        'title' => 'Día 4 · Puerta del Sol y Machu Picchu',
                        'description' => '<p>Salida de madrugada para llegar a Inti Punku con la primera luz. Desde ahí se baja caminando a la ciudadela para la visita guiada, y se vuelve a Cusco en tren y bus.</p>',
                    ],
                ],
                'en' => [
                    [
                        'title' => 'Day 1 · From the river to the first camp',
                        'description' => '<p>Transfer from Cusco to the checkpoint by the Urubamba where the trail begins. The first day is the kindest: a gradual climb through farmland, with the Inca settlement of Llactapata in plain view from the path. Camp is in a valley village.</p>',
                    ],
                    [
                        'title' => 'Day 2 · The Warmiwañusca pass',
                        'description' => '<p>The hard one. A steady climb to the highest point on the trail, then a long drop into the next valley. The pace is slow and there are plenty of stops — up here the altitude costs more than the distance does.</p>',
                    ],
                    [
                        'title' => 'Day 3 · Ruins in the clouds',
                        'description' => '<p>The most beautiful stretch. Runkurakay, Sayacmarca and Phuyupatamarca come one after another as the landscape turns from high grassland into cloud forest. The day ends at Wiñay Wayna, above its terraces.</p>',
                    ],
                    [
                        'title' => 'Day 4 · The Sun Gate and Machu Picchu',
                        'description' => '<p>A start in the dark to reach Inti Punku for first light. From there you walk down into the citadel for the guided visit, then head back to Cusco by train and bus.</p>',
                    ],
                ],
            ],
            'price_pen_cents' => 350000,
            // El unico precio que este lote TOCA. Los otros dos tours ya
            // cumplian exactamente PEN / 3.75 (el tipo de cambio guardado en
            // settings.exchange_rate_pen_usd): 12000/3.75=3200 y
            // 45000/3.75=12000. Este traia 9500 (US$ 95.00) junto a
            // S/ 3,500.00 en la MISMA ficha -- un tipo de cambio implicito de
            // 36.8 que el ojo detecta al instante. 350000/3.75 = 93333
            // (US$ 933.33). Es aritmetica sobre una cifra que ya existia, no
            // una cifra nueva; igual queda anotado en
            // docs/lote-3/contenido-demo-2026-09-14.md porque sigue siendo un
            // numero que la clienta tiene que confirmar.
            'price_usd_cents' => 93333,
            'is_featured' => true,
            'is_published' => true,
            'order' => 1,
            'meta_title' => [
                'es' => 'Camino Inca a Machu Picchu, 4 días | Pacha Viva',
                'en' => 'Inca Trail to Machu Picchu, 4 Days | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Trekking de cuatro días por el Camino Inca original hasta Machu Picchu, con guía oficial, porteadores y campamento. Sale desde Cusco.',
                'en' => 'A four-day trek along the original Inca Trail to Machu Picchu, with a licensed guide, porters and camping support. Departs from Cusco.',
            ],
        ], [$trekking->id, $cultura->id]);

        $this->upsertTour('muestra-tour-gastronomico-arequipa', [
            'destination_id' => $arequipa->id,
            'title' => [
                'es' => 'Ruta de picanterías en Arequipa',
                'en' => 'Arequipa Picantería Food Trail',
            ],
            'slug' => [
                'es' => 'ruta-de-picanterias-arequipa',
                'en' => 'arequipa-picanteria-food-trail',
            ],
            'summary' => [
                'es' => 'Del mercado San Camilo a las picanterías de siempre, probando la cocina que le dio fama a Arequipa.',
                'en' => 'From the San Camilo market to the old picanterías, tasting the cooking that made Arequipa famous.',
            ],
            'description' => [
                'es' => '<p>La picantería es una institución arequipeña: cocina de leña, ollas de barro y una carta que cambia según el día de la semana. La ruta empieza en el mercado San Camilo, donde se aprende a reconocer la materia prima —rocoto, huacatay, quesos de la campiña, maíces de todos los colores— y la sigue hasta la mesa donde se convierte en plato.</p>'
                    .'<p>Se come sentado y sin apuro, con alguien explicando qué es cada cosa y por qué se prepara así. Es un recorrido corto, en parte a pie y en parte en traslado, que funciona muy bien como primera tarde en la ciudad.</p>',
                'en' => '<p>The picantería is an Arequipa institution: wood-fired stoves, clay pots, and a menu that changes with the day of the week. This route starts in the San Camilo market, where you learn to spot the raw material — rocoto peppers, huacatay, cheeses from the surrounding farmland, corn in every colour — and follows it through to the table where it becomes lunch.</p>'
                    .'<p>You eat sitting down and unhurried, with someone explaining what each dish is and why it is made that way. It is a short route, partly on foot and partly by car, and it works well as a first afternoon in the city.</p>',
            ],
            'duration_label' => ['es' => '4 horas', 'en' => '4 hours'],
            'difficulty' => TourDifficulty::Facil,
            'meeting_point' => ['es' => 'Plaza de Armas, Arequipa', 'en' => 'Plaza de Armas, Arequipa'],
            'inclusions' => [
                'es' => [
                    'Guía local especializado en cocina arequipeña',
                    'Visita guiada al mercado San Camilo',
                    'Degustaciones en cada parada del recorrido',
                    'Almuerzo en una picantería tradicional',
                    'Traslados entre las paradas',
                ],
                'en' => [
                    'Local guide who knows the Arequipa kitchen',
                    'Guided walk through the San Camilo market',
                    'Tastings at every stop on the route',
                    'Lunch at a traditional picantería',
                    'Transport between stops',
                ],
            ],
            'exclusions' => [
                'es' => [
                    'Bebidas alcohólicas fuera de la degustación',
                    'Recojo y retorno al hotel',
                    'Propinas',
                    'Gastos personales',
                ],
                'en' => [
                    'Alcoholic drinks beyond the tasting',
                    'Hotel pick-up and drop-off',
                    'Tips',
                    'Personal expenses',
                ],
            ],
            'itinerary' => [
                'es' => [
                    [
                        'title' => 'Mercado San Camilo',
                        'description' => '<p>Recorrido por los pasillos del mercado con el guía: ajíes, hierbas, quesos y fruta de la campiña arequipeña. Primera parada de degustación en los puestos de jugos y de queso helado.</p>',
                    ],
                    [
                        'title' => 'Picantería tradicional',
                        'description' => '<p>Almuerzo en una picantería de las de siempre, con chicha de guiñapo sobre la mesa y el plato que toca según la costumbre semanal: chupes, adobo, rocoto relleno.</p>',
                    ],
                    [
                        'title' => 'Sobremesa con vista a la campiña',
                        'description' => '<p>Café y sobremesa mirando la campiña antes de volver al centro. Es el momento de preguntar por recetas, mercados y a dónde regresar por cuenta propia.</p>',
                    ],
                ],
                'en' => [
                    [
                        'title' => 'San Camilo market',
                        'description' => '<p>A walk through the market aisles with the guide: chillies, herbs, cheeses and fruit from the Arequipa countryside. First tasting stop at the juice stands and the queso helado counter.</p>',
                    ],
                    [
                        'title' => 'A traditional picantería',
                        'description' => '<p>Lunch at a long-standing picantería, with chicha de guiñapo on the table and whichever dish the weekly custom calls for: chupes, adobo, rocoto relleno.</p>',
                    ],
                    [
                        'title' => 'Sobremesa over the farmland',
                        'description' => '<p>Coffee and a long sobremesa looking out over the countryside before heading back into town — the moment to ask about recipes, markets, and where to return on your own.</p>',
                    ],
                ],
            ],
            'price_pen_cents' => 12000,
            'price_usd_cents' => 3200,
            'is_featured' => true,
            'is_published' => true,
            'order' => 2,
            'meta_title' => [
                'es' => 'Ruta de picanterías en Arequipa | Pacha Viva',
                'en' => 'Arequipa Picantería Food Trail | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Mercado San Camilo, picantería tradicional y sobremesa con vista a la campiña: la cocina arequipeña explicada en la mesa.',
                'en' => 'San Camilo market, a traditional picantería and a long sobremesa over the farmland: the Arequipa kitchen explained at the table.',
            ],
        ], [$gastronomia->id]);

        $this->upsertTour('muestra-tour-valle-sagrado', [
            'destination_id' => $valleSagrado->id,
            'title' => [
                'es' => 'Valle Sagrado: Pisac, Maras y Moray',
                'en' => 'Sacred Valley: Pisac, Maras and Moray',
            ],
            'slug' => [
                'es' => 'valle-sagrado-pisac-maras-moray',
                'en' => 'sacred-valley-pisac-maras-moray',
            ],
            'summary' => [
                'es' => 'Un día completo por el valle del Urubamba: el mercado y los andenes de Pisac, las salineras de Maras, los círculos de Moray y Ollantaytambo.',
                'en' => 'A full day in the Urubamba valley: the market and terraces of Pisac, the Maras salt pans, the circles of Moray, and Ollantaytambo.',
            ],
            'description' => [
                'es' => '<p>El Valle Sagrado se recorre bien en una sola jornada si se sale temprano. Está más abajo que Cusco y se respira mejor, así que es la salida que solemos recomendar para el primer o el segundo día del viaje, antes de subir a cualquier ruta de altura.</p>'
                    .'<p>El día junta paisajes que no se parecen en nada entre sí: el mercado de Pisac y sus andenes abiertos sobre el pueblo, las pozas blancas de Maras escalonadas sobre la quebrada, los andenes circulares de Moray —donde los incas probaron qué cultivo aguantaba a qué altura— y Ollantaytambo, que sigue habitado sobre su trazado inca.</p>',
                'en' => '<p>The Sacred Valley works well as a single day if you leave early. It sits lower than Cusco and is easier to breathe in, which is why we usually suggest it for the first or second day of a trip, before anyone takes on a high-altitude route.</p>'
                    .'<p>The day strings together landscapes with nothing in common: the market at Pisac and the terraces fanning out above the town, the white pools of Maras stacked down the ravine, the circular terraces of Moray — where the Incas worked out which crop could survive at which height — and Ollantaytambo, still lived in on its Inca street plan.</p>',
            ],
            'duration_label' => ['es' => '1 día', 'en' => 'Full day'],
            'difficulty' => TourDifficulty::Facil,
            'meeting_point' => ['es' => 'Recojo en el hotel, Cusco', 'en' => 'Hotel pick-up in Cusco'],
            'inclusions' => [
                'es' => [
                    'Transporte turístico durante todo el recorrido',
                    'Guía oficial de turismo en español o inglés',
                    'Recojo y retorno al hotel en Cusco',
                    'Parada en el mirador de las salineras de Maras',
                ],
                'en' => [
                    'Tourist transport for the whole route',
                    'Licensed guide, in Spanish or English',
                    'Hotel pick-up and drop-off in Cusco',
                    'Stop at the Maras salt pans viewpoint',
                ],
            ],
            'exclusions' => [
                'es' => [
                    'Boleto turístico del Valle Sagrado',
                    'Ingreso a las salineras de Maras',
                    'Almuerzo',
                    'Propinas',
                ],
                'en' => [
                    'Sacred Valley tourist ticket',
                    'Maras salt pans entrance fee',
                    'Lunch',
                    'Tips',
                ],
            ],
            'itinerary' => [
                'es' => [
                    [
                        'title' => 'Pisac',
                        'description' => '<p>Primera parada en el mercado de Pisac y subida al conjunto arqueológico, con sus andenes abiertos en abanico sobre el pueblo y los nichos funerarios en la ladera de enfrente.</p>',
                    ],
                    [
                        'title' => 'Salineras de Maras',
                        'description' => '<p>Mirador sobre las pozas de sal, que las familias de Maras siguen trabajando por turnos como se hacía desde antes de los incas. El agua sale ya salada de un manantial de la quebrada.</p>',
                    ],
                    [
                        'title' => 'Moray',
                        'description' => '<p>Los andenes circulares, encajados unos dentro de otros. Entre el anillo de arriba y el fondo hay una diferencia de temperatura que los incas aprovecharon para ensayar cultivos.</p>',
                    ],
                    [
                        'title' => 'Ollantaytambo',
                        'description' => '<p>Última parada: el templo sobre la ladera, los monolitos de piedra rosada traídos desde la otra margen del valle, y las calles del pueblo con sus canales de agua todavía en uso.</p>',
                    ],
                ],
                'en' => [
                    [
                        'title' => 'Pisac',
                        'description' => '<p>First stop at the Pisac market, then up to the archaeological site, with its terraces fanning out above the town and the burial niches on the hillside opposite.</p>',
                    ],
                    [
                        'title' => 'The Maras salt pans',
                        'description' => '<p>The viewpoint over the salt pools, still worked in shifts by the families of Maras much as they were before the Incas. The water comes out of a spring in the ravine already salty.</p>',
                    ],
                    [
                        'title' => 'Moray',
                        'description' => '<p>The circular terraces, set one inside the next. There is a real temperature difference between the top ring and the floor, and the Incas used it to test crops.</p>',
                    ],
                    [
                        'title' => 'Ollantaytambo',
                        'description' => '<p>Last stop: the temple on the hillside, the pink monoliths hauled across from the far side of the valley, and the streets of the town below, with their water channels still running.</p>',
                    ],
                ],
            ],
            'price_pen_cents' => 45000,
            'price_usd_cents' => 12000,
            'is_featured' => true,
            'is_published' => true,
            'order' => 3,
            'meta_title' => [
                'es' => 'Valle Sagrado: Pisac, Maras y Moray | Pacha Viva',
                'en' => 'Sacred Valley: Pisac, Maras and Moray | Pacha Viva',
            ],
            'meta_description' => [
                'es' => 'Día completo por el Valle Sagrado desde Cusco: Pisac, las salineras de Maras, los andenes circulares de Moray y Ollantaytambo.',
                'en' => 'A full day in the Sacred Valley from Cusco: Pisac, the Maras salt pans, the circular terraces of Moray, and Ollantaytambo.',
            ],
        ], [$cultura->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertDestination(string $legacySlug, array $attributes): Destination
    {
        /** @var Destination */
        return $this->upsert(new Destination, $legacySlug, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertExperience(string $legacySlug, array $attributes): Experience
    {
        /** @var Experience */
        return $this->upsert(new Experience, $legacySlug, $attributes);
    }

    /**
     * sync() rather than syncWithoutDetaching(): this seeder declares the
     * COMPLETE demo state, so a second run has to converge on exactly the
     * listed experiences, not on "the listed ones plus whatever was already
     * attached".
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $experienceIds
     */
    private function upsertTour(string $legacySlug, array $attributes, array $experienceIds): Tour
    {
        /** @var Tour $tour */
        $tour = $this->upsert(new Tour, $legacySlug, $attributes);

        $tour->experiences()->sync($experienceIds);

        return $tour;
    }

    /**
     * Looks the record up by its CURRENT "es" slug first, then by the slug
     * the previous version of this seeder used. That second lookup is what
     * turns a slug rename into an UPDATE of the existing row — which also
     * files the old slug in tour_slug_histories, so the old URL keeps
     * 301-ing — instead of silently creating a duplicate next to it.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(Destination|Experience|Tour $model, string $legacySlug, array $attributes): Destination|Experience|Tour
    {
        $currentSlug = $attributes['slug']['es'];

        $record = $model->newQuery()->where('slug->es', $currentSlug)->first()
            ?? $model->newQuery()->where('slug->es', $legacySlug)->first();

        if ($record === null) {
            return $model->newQuery()->create($attributes);
        }

        $record->fill($attributes)->save();

        return $record;
    }
}
