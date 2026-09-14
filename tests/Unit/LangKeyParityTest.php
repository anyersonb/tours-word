<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Guardian del objetivo 1 (lote i18n, 2026-09-14): si alguien agrega una
 * clave a lang/es/* y se olvida de lang/en/*, este test falla. Sin esto,
 * una clave nueva en español queda muda en inglés -- exactamente el
 * "site.hero_title" crudo delante de un visitante que motivó este lote.
 * Compara los TRES archivos de idioma que arma la interfaz (site,
 * contact-form, validation); el contenido de catálogo (Tour/Destination/
 * Experience) no vive en lang/, así que no aplica acá -- ese lo escribe la
 * clienta desde el CMS, nunca este archivo.
 *
 * Verificado manualmente (ver el reporte del lote): quitar una clave real
 * de lang/en/site.php hace fallar este mismo mecanismo (exit 1), y
 * restaurarla lo vuelve a poner en verde (exit 0) -- nunca se debilitó el
 * chequeo para simular el fallo.
 */
class LangKeyParityTest extends TestCase
{
    /**
     * @param  array<array-key, mixed>  $array
     * @return list<string>
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            // Listas indexadas numéricamente (items de FAQ, "features", etc.)
            // son bloques de contenido repetibles, no un set de claves fijo:
            // se compara su FORMA (a través del primer elemento bajo "[]"),
            // no cuántos elementos trae cada lista.
            $segment = is_int($key) ? '[]' : (string) $key;
            $path = $prefix === '' ? $segment : "{$prefix}.{$segment}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $path));

                continue;
            }

            $keys[] = $path;
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function keysOf(string $file): array
    {
        $data = require $file;

        return array_values(array_unique($this->flattenKeys($data)));
    }

    public function test_every_spanish_interface_key_has_an_english_counterpart(): void
    {
        $files = ['site.php', 'contact-form.php', 'validation.php'];
        $base = lang_path();

        $missingInEnglish = [];

        foreach ($files as $file) {
            $esPath = "{$base}/es/{$file}";
            $enPath = "{$base}/en/{$file}";

            $this->assertFileExists($esPath, "Falta {$esPath}");
            $this->assertFileExists($enPath, "Falta {$enPath}");

            $esKeys = $this->keysOf($esPath);
            $enKeys = $this->keysOf($enPath);

            foreach (array_diff($esKeys, $enKeys) as $key) {
                $missingInEnglish[] = "{$file}: {$key}";
            }
        }

        $this->assertSame(
            [],
            $missingInEnglish,
            "Claves en español sin traducción al inglés (lang/en/*):\n".implode("\n", $missingInEnglish)
        );
    }
}
