<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idiomas del catálogo
    |--------------------------------------------------------------------------
    |
    | "locales" es el universo completo que el ESQUEMA soporta (columnas JSON
    | traducibles en Tour, Destination, Experience, TourImage). "active_locales"
    | es el subconjunto que el panel de Filament expone como pestañas editables
    | HOY. Español está activo desde el lote 2; EN y PT-BR ya tienen columna
    | pero se activan recién en el lote 5 (ver docs/lote-2/00-contrato-datos.md).
    | Migrar contenido a traducible más tarde es carísimo: por eso el esquema
    | ya soporta los tres idiomas aunque el panel solo edite uno.
    |
    */

    'locales' => [
        'es' => 'Español',
        'en' => 'English',
        'pt_BR' => 'Português (Brasil)',
    ],

    // Alcance corregido por Anyerson el 2026-09-10: el sitio es SOLO español
    // e inglés; PT-BR queda fuera del proyecto (se deja inerte en "locales"
    // porque el esquema y LocalePrefixRoutingTest ya lo referencian).
    // Inglés activado el 2026-09-14 (lote i18n): lang/en/site.php,
    // contact-form.php y validation.php ya tienen paridad de claves 1:1 con
    // español (ver el diff de claves del reporte del lote), así que "/en/"
    // ya no muestra claves crudas.
    'active_locales' => ['es', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Contenido de muestra del catálogo
    |--------------------------------------------------------------------------
    |
    | true mientras tours/destinos/experiencias siguen sembrados por
    | DemoTourSeeder (lote 3): fuerza "noindex" en las fichas de destino y
    | experiencia, independientemente de si el contenido está traducido,
    | porque publicar un "Cusco" o "Trekking" de muestra en un motor de
    | búsqueda sería indexar contenido inventado. Extraído a config (en vez
    | de quedar cableado como `:noindex="true"` en el Blade) exactamente por
    | la misma razón que "active_locales" ya es config: es el único lugar que
    | hay que tocar para levantarlo cuando la clienta cargue contenido real
    | -- a partir de ahí, el noindex de cada ficha depende SOLO de si esa
    | ficha tiene traducción real al locale de la URL (ver "indexación del
    | inglés" del lote SEO, resources/views/destinations/show.blade.php y
    | resources/views/experiences/show.blade.php).
    |
    | ALCANCE REAL desde 7d25bf0: ya no es solo "las fichas del catálogo".
    | components/layout.blade.php hace `$isNoindex = $noindex ||
    | config('cms.catalog_demo_content')`, así que mientras esto esté en
    | true el sitio ENTERO va noindex -- home, nosotros y contacto
    | incluidos, en los dos locales activos. Por eso también cuelgan de
    | aquí, vía App\Support\Indexability, sitemap.xml (que si no anunciaría
    | justo esas páginas noindex: señal contradictoria) y la directiva
    | "Sitemap:" de robots.txt. Bajar esta bandera devuelve las tres cosas a
    | la vez, sin tocar código. Lo que NO cuelga de aquí y no debe colgar
    | nunca: el permiso de rastreo de robots.txt, porque poder descargar la
    | página es condición necesaria para que el noindex se llegue a leer.
    |
    */

    'catalog_demo_content' => true,

    /*
    |--------------------------------------------------------------------------
    | Espejo de staging (bandera dedicada, NO reutilizar catalog_demo_content)
    |--------------------------------------------------------------------------
    |
    | Receta SEO `docs/rediseno-2026/02-seo.md` ## 6.2. Este build se publica
    | como demo en `limaviewtours.com/tour-word/` -- un subdirectorio del
    | dominio de OTRO cliente, que ya sufrió en julio 2026 un incidente real
    | de staging indexado. Cero margen para repetirlo.
    |
    | A propósito una bandera DISTINTA de "catalog_demo_content": esa se
    | apaga el día que la clienta cargue contenido real (otro ciclo de vida,
    | decisión de negocio sobre el catálogo). Esta se apaga el día que el
    | build deje de vivir en el subdirectorio del dominio ajeno (decisión de
    | infraestructura/despliegue). Si compartieran la bandera, apagar una
    | apagaría la otra sin que nadie lo decidiera a propósito -- el día que
    | el catálogo pase a real, el staging quedaría indexable sin control.
    |
    | Solo se enciende con IS_STAGING_MIRROR=true en el .env DEL DESPLIEGUE
    | de /tour-word/, nunca en el .env del dominio real de Pacha Viva.
    |
    | FALLA CERRADA a propósito (hallazgo M-1, corregido tras
    | docs/rediseno-2026/04-seguridad.md ##5). Hasta este fix la clave era
    | `env('IS_STAGING_MIRROR', false)`: CUALQUIER valor falsy de PHP --
    | clave ausente por un .env perdido, un typo en el nombre de la
    | variable, una cadena vacía, o "0" -- se leía igual que un "false"
    | explícito y dejaba el espejo INDEXABLE. Medido en vivo por
    | security-engineer: cinco modos de fallo, los cinco silenciosos, los
    | cinco indexables -- justo el incidente que esta bandera existe para
    | prevenir (staging duplicado indexado en el dominio de otro cliente,
    | julio 2026).
    |
    | Ahora: solo IS_STAGING_MIRROR=false ESCRITO A PROPÓSITO (que Laravel
    | castea a bool(false)) produce `false`. Cualquier otro estado -- key
    | ausente, ".env" perdido, typo, vacío, "0", o cualquier cosa que no
    | sea exactamente la palabra "false" -- cae al default `true`
    | (noindex). Ante la duda, no indexable.
    |
    | Costo que hay que aceptar a cambio: el .env de producción REAL de
    | Pacha Viva (su propio dominio, NO el espejo) tiene que declarar
    | IS_STAGING_MIRROR=false EXPLÍCITO -- ya no basta con omitir la
    | variable. Ese fallo es ruidoso y barato (se nota en la primera
    | revisión de SEO, se arregla con una línea); el anterior era
    | silencioso y caro (se nota cuando el sitio de otro cliente ya está
    | en Google).
    |
    | App\Support\Indexability::siteIsIndexable() combina esta bandera con
    | catalog_demo_content comparando ambas contra `false` en estricto:
    | hace falta que las DOS resuelvan a `false` explícito para que el
    | sitio sea indexable. Todo lo que cuelga de esa clase (meta robots del
    | layout, sitemap.xml, "Sitemap:" de robots.txt) hereda el
    | comportamiento sin tocar ningún otro archivo.
    |
    */

    'is_staging_mirror' => env('IS_STAGING_MIRROR', true) !== false,

    /*
    |--------------------------------------------------------------------------
    | Moneda
    |--------------------------------------------------------------------------
    |
    | Monedas soportadas desde el día uno. El tipo de cambio real vive en la
    | tabla settings (clave "exchange_rate_pen_usd"), editable en el panel;
    | esto solo declara qué monedas existen y sus símbolos de despliegue.
    | Ningún componente debe cablear "S/" o "$" fuera de App\Support\Money.
    |
    */

    'currencies' => [
        'PEN' => ['symbol' => 'S/', 'name' => 'Sol peruano'],
        'USD' => ['symbol' => 'US$', 'name' => 'Dólar estadounidense'],
    ],

    'default_currency' => 'PEN',

];
