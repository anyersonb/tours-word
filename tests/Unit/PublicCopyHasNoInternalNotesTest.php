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
 *
 * Lo que NO es una nota interna y por eso no se persigue acá: decir que algo
 * del NEGOCIO todavía no existe. "Todavía no configuramos ningún dato de
 * contacto público. Vuelve pronto." es la redacción que la clienta aprobó
 * expresamente, y "Mapa referencial — pendiente de dirección real" también.
 * Hablan en primera persona de la agencia y no destapan el andamio.
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
     * roto. Estas son las seis redacciones que había publicadas el
     * 2026-09-14, textuales.
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
            'mapa ES' => ['Todavía no configuramos una dirección. En cuanto la clienta la confirme, vas a poder ver cómo llegar acá.'],
            'mapa EN' => ['We haven\'t set up an address yet. As soon as the client confirms it, you\'ll be able to see how to get here.'],
            'boletin ES' => ['Newsletter — próximamente, sin envío real todavía'],
            'boletin EN' => ['Newsletter — coming soon, no real submission yet'],
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
     * Y el otro lado del filo: las redacciones que la clienta aprobó no
     * pueden dispararlo, o el guardián acabaría empujando a borrar los avisos
     * honestos que ella pidió mantener.
     *
     * @return array<string, array{0: string}>
     */
    public static function approvedCopyThatMustNotTrip(): array
    {
        return [
            'datos de contacto ES' => ['Todavía no configuramos ningún dato de contacto público. Vuelve pronto.'],
            'datos de contacto EN' => ['We haven\'t set up any public contact details yet. Check back soon.'],
            'mapa ES' => ['Mapa referencial — pendiente de dirección real'],
            'mapa EN' => ['Placeholder map — pending real address'],
            'buscador' => ['Search — coming soon'],
        ];
    }

    #[DataProvider('approvedCopyThatMustNotTrip')]
    public function test_the_guard_leaves_the_approved_honest_notices_alone(string $text): void
    {
        foreach ($this->forbiddenPatterns() as $pattern) {
            $this->assertSame(
                0,
                preg_match($pattern, $text),
                "El guardián marca como nota interna un aviso que la clienta aprobó: {$text} ({$pattern})"
            );
        }
    }
}
