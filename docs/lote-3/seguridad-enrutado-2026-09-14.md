# Auditoría de seguridad: enrutado bilingüe y redirecciones 301 por slug

- Fecha: 2026-09-14
- Repo: `G:\laragon\www\tours-word`, rama `lote-1-sistema-diseno`, HEAD `3b700f9`
- Sitio vivo: `http://tours-word.test` (Apache 2.4.54 + PHP 8.2.1, Laragon)
- Alcance: SOLO la superficie tocada por `3b700f9` — redirecciones 301 derivadas del segmento
  de URL que escribe el visitante, mapa de alternativas de idioma, canonical/hreflang.
- Fuera de alcance por indicación del solicitante: panel `/admin` (Filament), `noindex` global
  (intencional, contenido de muestra), `storage/app/public/.htaccess` (ya verificado).
- Restricción operativa cumplida: NO se ejecutó la suite de tests (BD de pruebas compartida con
  otro agente). Todo lo ejecutado fue **solo lectura**: peticiones GET contra el sitio vivo,
  `SELECT` por tinker, y un arnés que despacha requests GET por el kernel HTTP.
- **No se aplicó ningún fix. No se modificó ningún archivo del proyecto** salvo este informe.

## Veredicto

**APROBADO CON OBSERVACIONES.**

No hay hallazgos críticos ni altos: **la superficie del commit `3b700f9` sale limpia en las cinco
preguntas**. El único hallazgo real (M-01, bucle infinito de 301) vive en
`app/Http/Middleware/SetLocaleFromUrl.php`, que no está en el commit auditado pero sí en el mismo
lote (Objetivo 5) y en la misma cadena de redirecciones de estas URLs. Por severidad (Medio)
**no bloquea producción**, pero debe cerrarse antes de que el sitio baje el `noindex`: hoy es un
bucle de redirección permanente alcanzable en cualquier URL pública del grupo.

---

## 1. Redirección abierta — NO EXPLOTABLE

No se consiguió sacar el 301 del dominio.

**a) El destino del 301 canónico no sale de la URL.** `TourController::show()` (línea 92),
`DestinationController::show()` (72) y `ExperienceController::show()` (60) construyen el destino
con `redirect()->route(..., ['locale' => $locale, 'slug' => $canonicalSlug], 301)`.
`$canonicalSlug` sale de la columna JSON `slug` del registro (`canonicalSlugFor()`), no de la
petición; el slug pedido solo se usa como término de comparación. `$locale` es el parámetro de
ruta **ya decodificado**, filtrado por `SetLocaleFromUrl` (aborta 404 si no es un locale activo)
y por el patrón de ruta `[A-Za-z-]+`.

**b) El 301 de normalización de mayúsculas sí arma la URL desde el path**, pero el resultado
siempre empieza por `/<locale>`, nunca por `//`. Intentos, con la respuesta real:

```
//ES/tours/inca-trail-machu-picchu-4-days -> 404
//EN/nosotros                             -> 404
///EN/nosotros                            -> 404
/\/EN/nosotros                            -> 404
//evil.tld/ES/nosotros                    -> 404   (el patrón [A-Za-z-]+ rechaza el punto)
/%2f%2fevil.tld/                          -> 404
```

Los 404 de Apache se reprodujeron **sin Apache**, despachando la request por el kernel HTTP con
`REQUEST_URI` crudo, para descartar un falso negativo por normalización del servidor
(`MergeSlashes`). Mismo resultado en los dos instrumentos.

**c) Credenciales embebidas / esquema.** `route()` genera siempre URL absoluta con el host de la
petición. Comprobado que un slug hostil no rompe el destino (tinker, solo lectura):

```
a"b<c       => http://.../es/tours/a%22b%3Cc
//evil.tld  => http://.../es/tours///evil.tld     (sigue siendo el propio host)
a%0d%0aX:1  => http://.../es/tours/a%0d%0aX:1     (no se decodifica)
```

**d) El 301 de barra final del `.htaccess`** tampoco sale del dominio: `//evil.tld/` responde
`301 Location: http://tours-word.test/evil.tld` (Apache fusiona las barras antes de componer el
`Location`).

> Observación B-01 (Bajo, defensa en profundidad): `route()` **no** codifica la barra en el
> parámetro `{slug}`. Si el CMS llegara a guardar un slug con `/`, el `Location` del 301
> contendría segmentos extra (`/es/tours///evil.tld`). Sigue siendo el mismo origen, así que no
> es redirección abierta, pero conviene que el guardado de slug en Filament rechace `/` y `\`.

## 2. Bucles y agotamiento — LA AFIRMACIÓN DEL AUTOR ES CORRECTA PARA SU CÓDIGO; HAY UN BUCLE AL LADO

**La parte del commit es correcta.** `canonicalSlugFor()` compara contra el slug **pedido** y
`slugLookupLocales()` prueba **primero el locale de la URL**: el destino del 301 es siempre un
slug que existe en el locale de la URL, por lo que en el salto siguiente `canonicalSlugFor()`
devuelve `null` y se sirve 200. Medido:

```
/en/tours/camino-inca-machu-picchu-4-dias -> 301 -> /en/tours/inca-trail-machu-picchu-4-days -> 200
/es/tours/inca-trail-machu-picchu-4-days  -> 301 -> /es/tours/camino-inca-machu-picchu-4-dias -> 200
```

Cadenas máximas medidas (barra final de Apache + mayúsculas + canónico):

```
/ES/tours/camino-inca-machu-picchu-4-dias/  -> hops=2 -> 200
/EN/tours/camino-inca-machu-picchu-4-dias/  -> hops=3 -> 200   (la más larga encontrada)
/es/tours/muestra-camino-inca-4-dias        -> hops=1 -> 200   (tour_slug_histories)
/EN/tours/muestra-camino-inca-4-dias/       -> hops=2 -> 404
```

Ninguna cadena supera 3 saltos y todas terminan.

### M-01 [MEDIO] Bucle infinito de 301 con el segmento de idioma percent-codificado

- **Ubicación:** `app/Http/Middleware/SetLocaleFromUrl.php:44-56` (bloque "Objetivo 5"). Fuera de
  los 8 archivos de `3b700f9`, dentro del mismo lote y de la misma cadena de 301.
- **Petición y respuesta reales:**

```
$ curl -i --path-as-is http://tours-word.test/%45%53/nosotros
HTTP/1.1 301 Moved Permanently
Location: http://tours-word.test/%45%53/nosotros      <-- a sí misma

$ curl -L --max-redirs 6 --path-as-is http://tours-word.test/%45%53/nosotros
hops=6 final=301 destino=http://tours-word.test/%45%53/nosotros   (curl aborta, exit 47)
```

  Control negativo con el mismo comando: `/es/nosotros -> hops=0 final=200`. Reproducido en todas
  las rutas del grupo y en ambos idiomas: `/E%53/nosotros`, `/%65%53/nosotros`,
  `/%45%53/tours/inca-trail-machu-picchu-4-days`, `/%45%53/destinos/cusco`,
  `/%45%4E/tours/camino-inca-machu-picchu-4-dias` — las seis, bucle. Confirmado también sin
  Apache (kernel HTTP directo): `Location` idéntico a la URI pedida.
- **Causa:** el guard compara el segmento **decodificado** (`$request->route('locale')` = `ES`)
  contra el canónico (`es`) y decide redirigir; pero el
  `preg_replace('#^/ES#', '/es', $request->getPathInfo(), 1)` opera sobre el path **crudo**
  (`/%45%53/nosotros`), que no casa con el patrón. `preg_replace` devuelve el path intacto y se
  emite un 301 a la misma URL. Las dos mitades del `if` leen representaciones distintas del mismo
  segmento.
- **Vector / impacto:** cualquiera puede publicar un enlace así (o un rastreador generarlo al
  recodificar una URL) y quien lo siga recibe `ERR_TOO_MANY_REDIRECTS` en vez del sitio. El 301
  es cacheable de forma indefinida, así que el navegador de la víctima conserva el bucle para esa
  URL aunque el servidor ya esté arreglado. Amplificación menor: un clic genera ~20 peticiones,
  cada una arrancando el framework. No hay pérdida de datos ni salto de permisos.
- **Remediación (no aplicada):** construir el destino desde los segmentos **decodificados** y,
  sobre todo, **no emitir nunca un `Location` igual a la URI actual**:

```php
$segments = $request->segments();          // ya decodificados
$segments[0] = $canonicalSegment;
$target = '/'.implode('/', array_map('rawurlencode', $segments));

// Red de seguridad: si el destino es la propia URL, se sirve el contenido, no se redirige.
if (rawurldecode($target) !== rawurldecode($request->getPathInfo())) {
    return redirect()->to($target.($query ? '?'.$query : ''), 301);
}
```

  El guard del segundo `if` es lo que hace el bucle imposible por construcción, no la mejora del
  reemplazo: cualquier normalización que se añada en el futuro queda cubierta.

## 3. Inyección por el slug — NO EXPLOTABLE

**a) Cabecera `Location`.** No hay reflejo: el destino se compone con `route()` a partir del slug
de base de datos. Aun así se probó el reflejo directo; todas 404, sin cabecera anómala:

```
/es/tours/camino-inca-machu-picchu-4-dias%0d%0aX-Injected:%201   -> 404
/en/tours/camino-inca-machu-picchu-4-dias%00                     -> 404
/en/tours/..%2f..%2fevil                                         -> 404
/en/tours/%2f%2fevil.tld                                         -> 404
/en/tours/https:%2f%2fevil.tld                                   -> 404
```

**b) Consultas Eloquent.** `where("slug->{$tryLocale}", $slug)` interpola en el **nombre de
columna** solo valores de `config()` (`slugLookupLocales()` arma la lista con
`cms.active_locales` y `app.fallback_locale`), y el slug viaja como **binding**. Sondas:

```
/es/tours/%27%20or%20%271%27=%271   -> 404   (sin 500, sin traza)
/es/tours/%22%29%20or%201=1--       -> 404
/es/tours?destino[]=cusco           -> 200   (la validación alpha_dash de F-3 sigue viva)
/es/tours?destino=cusco%27          -> 200
```

Control que valida la medición: con `APP_DEBUG=true` en este entorno, un error de binding se
vería como 500 con traza. No se produjo en ninguna sonda.

**c) Ruptura de contexto en el `<head>`.** Los `hreflang`, el canonical y los enlaces del selector
se imprimen con `{{ }}` (`components/layout.blade.php:133,147,149`;
`components/header/locale-switcher.blade.php:47`), que escapa comillas y ángulos
(`e('a"><svg onload=1>')` => `a&quot;&gt;&lt;svg onload=1&gt;`). Además sus URLs las genera
`route()` con el slug de BD, no con el pedido. El intento de reflejo
`/es/tours/x"><svg onload=alert(1)>` devuelve 404 con la página de error de Laravel, que **no
reproduce la URL** (0 coincidencias de `svg onload` en el cuerpo).

> Observación B-02 (Bajo): el canonical de una página 200 sí refleja el path **crudo**
> (`url()->current()`): `/e%73/nosotros` responde 200 con
> `<link rel="canonical" href="http://tours-word.test/e%73/nosotros">`. Va escapado, así que no
> hay XSS; pero es espacio de URL duplicado y autocanonizado. Hoy lo tapa el `noindex`; el fix de
> M-01 (normalizar de verdad, en vez de comparar solo mayúsculas) lo cierra de paso.

> Observación B-03 (Bajo, latente): en los tres controladores,
> `$internalLocale = Locale::fromSegment($locale) ?? $locale;` deja el **segmento crudo** como
> clave del camino JSON `slug->{...}` si `fromSegment()` devolviera `null`. Hoy es inalcanzable
> (el middleware aborta 404 antes y el patrón de ruta solo admite `[A-Za-z-]+`; comprobado:
> `/%27/nosotros` y `/es%27/nosotros` -> 404). Si alguien quita el middleware de un grupo, ese
> fallback se convierte en inyección en el nombre de columna. Preferible
> `?? config('app.fallback_locale')`.

## 4. Fuga por el mapa de alternativas — NO SE REPRODUCE

`LocaleAlternates` está registrado con `scoped` (`AppServiceProvider:19`), no `singleton`, y el
proyecto **no** usa Octane (sin `laravel/octane` en `composer.json`), así que bajo PHP-FPM/mod_php
el contenedor se recrea en cada petición. Verificado además empíricamente.

Peticiones consecutivas, una detrás de otra:

```
A  /en/tours/inca-trail-machu-picchu-4-days  -> alterno ES = /es/tours/camino-inca-machu-picchu-4-dias
B  /es/nosotros                              -> alterno EN = /en/nosotros   (ni rastro del tour de A)
C  /es/destinos/valle-sagrado                -> alterno EN = /en/destinos/sacred-valley
```

12 peticiones **concurrentes** mezclando fichas y páginas estáticas (`xargs -P 12`), cada
respuesta con su propio alterno y ninguna con el de otra:

```
3 /es/contacto                              => <sin alternate EN de tour>
4 /es/nosotros                              => <sin alternate EN de tour>
1 /es/destinos/cusco                        => <sin alternate EN de tour>
2 /es/tours/camino-inca-machu-picchu-4-dias => /en/tours/inca-trail-machu-picchu-4-days
1 /es/tours/ruta-de-picanterias-arequipa    => /en/tours/arequipa-picanteria-food-trail
1 /es/tours/valle-sagrado-pisac-maras-moray => /en/tours/sacred-valley-pisac-maras-moray
```

El control que hace falsable la prueba: las páginas sin ficha muestran explícitamente "sin
alternate EN de tour". Si hubiera arrastre, ahí aparecería el slug de la ficha vecina.

## 5. Enumeración — NO DELATA RECURSOS DESPUBLICADOS

| Caso | Respuesta | Evidencia |
|---|---|---|
| Existe y está publicado en otro idioma | 301 al canónico | `/en/tours/camino-inca-machu-picchu-4-dias -> 301` |
| No existe | 404 | `/en/tours/CAMINO-INCA-MACHU-PICCHU-4-DIAS -> 404` (la búsqueda distingue mayúsculas) |
| Existe pero NO publicado | 404 | ver abajo |

`findBySlugForLocale()` aplica `->published()` (`ResolvesBySlugByLocale:60`), que es
`where('is_published', true)` (`Tour.php:179`, `Destination.php:88`, `Experience.php:78`). Un
registro despublicado devuelve `null` por el mismo camino que uno inexistente: en destinos y
experiencias cae en `abort_unless(..., 404)`; en tours pasa a `redirectFromHistory()`, que vuelve
a filtrar con `published()` antes de redirigir y si no, `abort(404)`. Ni el slug actual ni el
histórico de un tour despublicado producen un 301 delator.

**No verificado con un registro real:** en la BD los 9 registros (3+3+3) están publicados y la
consigna prohíbe mutar datos, así que no se creó un borrador para ejercitarlo. La conclusión sale
de la lectura del camino de código y del scope, no de una petición. Si se quiere certificar, el
caso pertenece a la suite (`LocaleCanonicalSlugTest`), no a una sonda en vivo.

---

## Verificaciones realizadas

- [✓] A01 Control de acceso — solo la superficie pública tocada: los tres `show()` filtran por
      `published()` y no exponen registros ocultos por código de respuesta.
- [✓] A03 Inyección — SQL por slug y por filtros del índice; XSS en canonical/hreflang/selector.
- [✓] A04 Diseño — bucles, agotamiento, longitud de cadena de redirección y enumeración.
- [✓] A05 Configuración — patrón de ruta del locale y middleware `locale` aplicado al grupo.
- [✓] A10 Redirección abierta / SSRF — el destino del 301 no es controlable por el visitante.
- [✓] Fuga de estado entre peticiones (`scoped`) — consecutivas y concurrentes.
- [ ] **No verificado:** suite de tests (prohibida en esta pasada, BD compartida).
- [ ] **No verificado:** comportamiento con un registro despublicado (no existe ninguno y no se
      permitió mutar datos) — ver sección 5.
- [ ] **No verificado:** comportamiento bajo Octane o worker persistente (no está instalado).
- [ ] **No verificado:** servidor de producción. Todo se midió contra el Apache 2.4.54 de Laragon;
      un nginx con `merge_slashes off` podría cambiar qué llega a la app en los casos con `//`.
      Repetir las cuatro sondas de la sección 1.b en el entorno real antes de publicar.
- [ ] Fuera de alcance por indicación: `/admin`, `noindex` global, `storage/app/public/.htaccess`.

## Recomendaciones de hardening

1. Cerrar **M-01** con el guard de "nunca redirigir a la propia URL". Es la única regla que
   sobrevive a futuras normalizaciones (idioma, barra final, mayúsculas, codificación).
2. **B-03**: cambiar `?? $locale` por `?? config('app.fallback_locale')` en los tres
   controladores, para que quitar el middleware no convierta un segmento de URL en nombre de
   columna.
3. **B-01**: validar el slug en el CMS contra `[a-z0-9-]+` al guardar (Filament), no solo por
   unicidad. Hoy el destino del 301 confía en que la columna está limpia.
4. Registrar en log la redirección que se emite cuando el destino ya redirigió antes: hoy ninguna
   de estas redirecciones deja rastro, y el bucle de M-01 solo se ve desde el cliente.
5. Antes de producción, confirmar `APP_DEBUG=false` y `APP_ENV=production` en el `.env` del
   servidor: en este entorno están en `true`/`local` (correcto en local, mortal en producción,
   porque una excepción de enrutado mostraría traza y configuración).
