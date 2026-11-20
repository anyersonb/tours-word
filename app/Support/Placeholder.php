<?php

namespace App\Support;

/**
 * CRO C-1 (docs/rediseno-2026/19-cro-mobile.md): el diseño y los datos de
 * muestra traen marcadores sin completar ("[PRECIO]", "[N] tours",
 * "[Respuesta por completar]"...). Un marcador NUNCA debe verse en el sitio
 * público. Regla única: todo texto que EMPIEZA por "[" es un marcador.
 *
 * No toca la base de datos ni los archivos lang/: solo decide qué se imprime.
 * Cuando la clienta complete el texto real, aparece solo.
 */
class Placeholder
{
    /**
     * Interruptor único (config brand.show_placeholders / SHOW_PLACEHOLDERS):
     * encendido = los marcadores se muestran literales como en el PDF de diseño.
     */
    public static function enabled(): bool
    {
        return (bool) config('brand.show_placeholders', false);
    }

    /** ¿Es un marcador sin completar? (null/vacío NO cuenta: eso es "sin dato"). */
    public static function is(mixed $value): bool
    {
        return is_string($value) && str_starts_with(ltrim($value), '[');
    }

    /** El texto, o null si es un marcador o está vacío. */
    public static function visible(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (self::is($value) && ! self::enabled()) {
            return null;
        }

        return $value;
    }

    /**
     * Quita el prefijo "[MUESTRA]" de títulos de datos demo, solo para
     * mostrarlos ("[MUESTRA] Camino Inca 4 días" -> "Camino Inca 4 días").
     */
    public static function title(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim(preg_replace('/^\s*\[\s*MUESTRA\s*\]\s*/iu', '', $value)) ?: $value;
    }

    /**
     * Descarta los ítems de una lista (testimonios, FAQ, sellos...) donde
     * ALGÚN valor de texto es un marcador. Reindexa.
     *
     * @param  iterable<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function items(iterable $items): array
    {
        if (self::enabled()) {
            return array_values(is_array($items) ? $items : iterator_to_array($items, false));
        }

        $kept = [];

        foreach ($items as $item) {
            foreach ((array) $item as $value) {
                if (self::is($value)) {
                    continue 2;
                }
            }
            $kept[] = $item;
        }

        return $kept;
    }
}
