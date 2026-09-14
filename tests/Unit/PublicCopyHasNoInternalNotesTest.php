<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * DEF-B (docs/lote-3/validacion-visual-2026-09-14.md, punto 5). La clienta
 * encontró "Acepto la política de privacidad (en preparación por la clienta)"
 * publicado en /es/contacto. No era una línea suelta: el mismo patrón estaba
 * en la respuesta de FAQ sobre cancelaciones, en el aviso del mapa y en el
 * campo del boletín de la portada, en los dos idiomas. El defecto es el
 * PATRÓN, así que el guardián vigila el patrón, no esa línea.
 *
 * Qué es una "nota interna" acá, con precisión:
 *
 *   1. Nombrar a nuestra contraparte contractual delante del visitante
 *      ("la clienta", "the client"). El visitante ES el cliente de la
 *      agencia; leer que hay OTRO cliente detrás rompe la ficción del
 *      negocio montado.
 *   2. Describir NUESTRO proceso de producción: quién tiene que redactar
 *      qué, qué está "en preparación", qué todavía "no envía de verdad".
 *   3. Delatar que lo que el visitante está viendo es un SUSTITUTO
 *      provisional puesto por quien construye el sitio: "referencial",
 *      "placeholder", "pendiente de <dato> real".
 *
 * Lo que NO es una nota interna y por eso no se persigue acá: decir que algo
 * del NEGOCIO todavía no existe. "Todavía no configuramos ningún dato de
 * contacto público. Vuelve pronto." es la redacción que la clienta aprobó
 * expresamente: habla en primera persona de la agencia y no destapa el
 * andamio.
 *
 * SEGUNDA VUELTA (la clienta, 2026-09-14). El punto 3 no existía. "Mapa
 * referencial — pendiente de dirección real" quedó clasificado como aviso
 * honesto porque ella lo había aprobado en el informe — y al revisarlo se
 * retractó: "sigue siendo lenguaje de obra, no de cliente". La frontera
 * correcta pasa por ahí: "todavía no configuramos una dirección" cuenta el
 * ESTADO DEL NEGOCIO (un dato que no existe); "pendiente de dirección real"
 * cuenta el ESTADO DE LA OBRA (hay un relleno puesto, esperando el dato).
 *
 * De ahí la regla de mantenimiento de las dos listas de abajo: en la de
 * "aprobadas" sólo entra lo que consta TEXTUALMENTE aprobado en un informe,
 * y una aprobación puede caducar. Que algo esté en esa lista no lo vuelve
 * correcto; sólo registra que alguien lo miró y dijo que sí.
 *
 * Nota de método: array_walk sobre los arrays de idioma sólo ve VALORES, así
 * que los comentarios de código (que sí dicen "PENDIENTE clienta", y deben
 * seguir diciéndolo) quedan fuera por construcción — nunca se renderizan.
 */
class PublicCopyHasNoInternalNotesTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function forbiddenPatterns(): array
    {
        return [
            // 1. Nuestra contraparte, nombrada en público.
            '/\b(?:la|el)\s+client[ae]\b/iu',
            '/\bthe\s+client\b/i',
            // 2. Nuestro proceso de producción, contado en público.
            '/en\s+preparaci[óo]n/iu',
            '/being\s+drafted/i',
            '/pendiente\s+de\s+aprobaci[óo]n/iu',
            '/pending\s+approval/i',
            '/sin\s+env[íi]o\s+real/iu',
            '/no\s+real\s+submission/i',
            // 3. El relleno, confesado. Segunda vuelta: es lo que delataba
            //    "Mapa referencial — pendiente de dirección real".
            '/\breferencial(es)?\b/iu',
            '/\bplaceholder\b/i',
            '/pendiente\s+de\s+\S+\s+real/iu',
            '/\bpending\s+real\b/i',
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function interfaceLangFiles(): array
    {
        $cases = [];

        foreach (['es', 'en'] as $locale) {
            foreach (['site.php', 'contact-form.php', 'validation.php'] as $file) {
                $cases["{$locale}/{$file}"] = ["{$locale}/{$file}"];
            }
        }

        return $cases;
    }

    /**
     * @return array<string, string> ruta de la clave => texto
     */
    private function stringsOf(string $relativePath): array
    {
        $path = lang_path($relativePath);

        $this->assertFileExists($path, "Falta {$path}");

        $strings = [];

        $walk = function (array $array, string $prefix) use (&$walk, &$strings): void {
            foreach ($array as $key => $value) {
                $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

                if (is_array($value)) {
                    $walk($value, $path);

                    continue;
                }

                if (is_string($value)) {
                    $strings[$path] = $value;
                }
            }
        };

        $walk(require $path, '');

        // Control del propio instrumento: una extracción vacía haría pasar
        // este test sin comprobar absolutamente nada (es el modo de fallo
        // clásico de un guardián por grep).
        $this->assertNotEmpty($strings, "No se extrajo ni una cadena de {$relativePath}: el chequeo no probaría nada.");

        return $strings;
    }

    #[DataProvider('interfaceLangFiles')]
    public function test_no_interface_string_talks_about_our_own_production_process(string $relativePath): void
    {
        $offenders = [];

        foreach ($this->stringsOf($relativePath) as $key => $text) {
            foreach ($this->forbiddenPatterns() as $pattern) {
                if (preg_match($pattern, $text) === 1) {
                    $offenders[] = "{$relativePath}: {$key} => {$text}";

                    break;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Nota interna publicada en un texto que ve el visitante:\n".implode("\n", $offenders)
        );
    }

    /**
     * El guardián al revés: si los patrones no detectaran la línea exacta que
     * reportó la clienta, el test de arriba estaría en verde sobre un sitio
     * roto. Estas son las redacciones que estuvieron PUBLICADAS el 2026-09-14,
     * textuales.
     *
     * Las dos últimas entraron en la segunda vuelta: estaban en la lista de
     * "aprobadas" de este mismo archivo y son justo lo que la clienta mandó
     * quitar. Mientras vivieron ahí, el guardián bendecía el defecto.
     *
     * @return array<string, array{0: string}>
     */
    public static function copyThatWasPublishedAndMustNeverComeBack(): array
    {
        return [
            'privacidad ES' => ['política de privacidad (en preparación por la clienta)'],
            'privacidad EN' => ['privacy policy (being drafted by the client)'],
            'faq cancelacion ES' => ['Todavía no publicamos esta política — la clienta debe redactarla y aprobarla antes de habilitar reservas en línea.'],
            'faq cancelacion EN' => ['We haven\'t published this policy yet — the client needs to draft and approve it before online bookings are enabled.'],
            'aviso de direccion ES' => ['Todavía no configuramos una dirección. En cuanto la clienta la confirme, vas a poder ver cómo llegar acá.'],
            'aviso de direccion EN' => ['We haven\'t set up an address yet. As soon as the client confirms it, you\'ll be able to see how to get here.'],
            'boletin ES' => ['Newsletter — próximamente, sin envío real todavía'],
            'boletin EN' => ['Newsletter — coming soon, no real submission yet'],
            // Segunda vuelta, 2026-09-14.
            'recuadro del mapa ES' => ['Mapa referencial — pendiente de dirección real'],
            'recuadro del mapa EN' => ['Placeholder map — pending real address'],
        ];
    }

    #[DataProvider('copyThatWasPublishedAndMustNeverComeBack')]
    public function test_the_guard_actually_catches_the_copy_that_was_reported(string $text): void
    {
        $caught = false;

        foreach ($this->forbiddenPatterns() as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $caught = true;

                break;
            }
        }

        $this->assertTrue($caught, "El guardián NO detecta una nota interna que sí estuvo publicada: {$text}");
    }

    /**
     * Y el otro lado del filo: un aviso honesto sobre el estado del NEGOCIO no
     * puede disparar el guardián, o acabaría empujando a borrar justo lo que
     * la clienta pidió mantener.
     *
     * Cada entrada dice de dónde sale su legitimidad. Sólo se etiqueta como
     * aprobado lo que consta textualmente en un informe — la segunda vuelta
     * salió de haber metido acá dos frases por inercia, y el guardián estuvo
     * bendiciendo el defecto todo ese rato.
     *
     * Revisión de la lista con el criterio afinado (2026-09-14, segunda
     * vuelta), caso por caso:
     *
     *   - Los dos avisos de "datos de contacto": aprobados textualmente por
     *     la clienta en el punto 5 del informe ("Me gusta que donde faltan mis
     *     datos no se inventa nada"). Hablan del negocio. Se quedan.
     *   - Los dos del recuadro del mapa: RETIRADOS, pasaron a la lista de
     *     detectadas. Hablaban de la obra.
     *   - "Search — coming soon" (y su gemelo del selector de idioma):
     *     RETIRADO de esta lista. No es andamio — anuncia una función, no
     *     confiesa un relleno — pero nadie lo aprobó nunca y no puedo
     *     declararlo aprobado. Queda sin cobertura a propósito y anotado como
     *     punto abierto: si esas dos funciones no entran antes de producción,
     *     la decisión de qué hacer con un control deshabilitado es de
     *     producto y de maqueta, no de este guardián.
     *
     * @return array<string, array{0: string}>
     */
    public static function honestBusinessNoticesThatMustNotTrip(): array
    {
        return [
            // Aprobadas textualmente por la clienta (informe, punto 5).
            'datos de contacto ES' => ['Todavía no configuramos ningún dato de contacto público. Vuelve pronto.'],
            'datos de contacto EN' => ['We haven\'t set up any public contact details yet. Check back soon.'],
            // Redacción vigente tras la segunda vuelta: si alguien endurece
            // los patrones y tumba el reemplazo, que lo diga este test y no
            // la clienta.
            'aviso de direccion ES' => ['Todavía no configuramos una dirección de oficina. Vuelve pronto.'],
            'aviso de direccion EN' => ['We haven\'t set up an office address yet. Check back soon.'],
            'recuadro del mapa ES' => ['Mapa ilustrativo'],
            'recuadro del mapa EN' => ['Illustrative map'],
        ];
    }

    #[DataProvider('honestBusinessNoticesThatMustNotTrip')]
    public function test_the_guard_leaves_the_honest_business_notices_alone(string $text): void
    {
        foreach ($this->forbiddenPatterns() as $pattern) {
            $this->assertSame(
                0,
                preg_match($pattern, $text),
                "El guardián marca como nota interna un aviso honesto sobre el negocio: {$text} ({$pattern})"
            );
        }
    }
}
