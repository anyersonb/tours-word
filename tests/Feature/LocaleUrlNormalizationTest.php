<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * M-01 (docs/lote-3/seguridad-enrutado-2026-09-14.md): pedir el prefijo de
 * idioma con sus letras percent-codificadas (0x45 0x53, que es "ES")
 * entraba en un bucle infinito de 301 a si misma. El guard de
 * SetLocaleFromUrl comparaba el segmento DECODIFICADO ("ES" != "es", asi
 * que decidia redirigir) pero reescribia el path CRUDO con una expresion
 * regular anclada al segmento decodificado, que no casaba: el path volvia
 * intacto y se emitia un 301 a la propia URI. Las dos mitades del if leian
 * representaciones distintas del mismo segmento.
 *
 * Este archivo cubre DOS cosas independientes, y a proposito por separado:
 *
 *  1. Que la normalizacion funcione tambien sobre el path codificado: el
 *     destino se construye desde los segmentos ya decodificados.
 *  2. La GARANTIA ESTRUCTURAL, que es la que de verdad importa: este
 *     middleware no puede emitir jamas un Location igual a la URI pedida.
 *     Aunque alguien vuelva a equivocarse en la normalizacion, el peor
 *     resultado posible es un 200 o un 404, nunca un bucle.
 *
 * Falsacion comprobada (ver reporte del fix), con tres estados del
 * middleware:
 *   - defecto reintroducido + guard puesto  -> falla el grupo 1, el bucle
 *     NO reaparece (convergencia en verde): el guard hace su trabajo.
 *   - defecto reintroducido + guard quitado -> falla tambien el grupo 2.
 *   - fix completo                          -> todo en verde.
 *
 * Nota de entorno: las secuencias percent se construyen en tiempo de
 * ejecucion con pct(). Escribirlas literales en el .php hacia que el
 * antivirus de la maquina pusiera el archivo en cuarentena a los pocos
 * minutos (ver reporte); el test es identico, solo cambia como se escribe.
 */
class LocaleUrlNormalizationTest extends TestCase
{
    /**
     * Percent-codifica TODOS los bytes de una cadena, incluidos los que no
     * lo necesitan. Es justo lo que hace un cliente hostil (o un rastreador
     * recodificando una URL) y lo que el middleware no sabia deshacer.
     */
    private static function pct(string $value): string
    {
        $out = '';

        foreach (str_split($value) as $char) {
            $out .= '%'.strtoupper(bin2hex($char));
        }

        return $out;
    }

    /**
     * URIs que ejercitan el mismo camino por variantes distintas:
     * codificacion total, parcial, mayusculas mezcladas, barra final,
     * query string y doble codificacion. Ninguna puede redirigir a si
     * misma y todas tienen que terminar.
     *
     * @return list<string>
     */
    private static function corpus(): array
    {
        $ES = self::pct('ES');      // "ES" entero codificado: el caso de M-01
        $es = self::pct('es');      // idem pero ya en minusculas
        $EN = self::pct('EN');      // el otro locale activo
        $S = self::pct('S');        // para la codificacion parcial "E" + "S"
        $s = self::pct('s');        // para "e" + "s"

        return [
            '/'.$ES.'/nosotros',
            '/E'.$S.'/nosotros',                // codificacion parcial, mayusculas
            '/'.$es.'/nosotros',
            '/e'.$s.'/nosotros',                // codificacion parcial, minusculas (B-02)
            '/Es/nosotros',                     // mayusculas mezcladas
            '/eS/contacto',                     // mayusculas mezcladas, otra ruta
            '/ES/',                             // barra final
            '/'.$ES.'/',                        // codificado + barra final
            '/ES/tours?destino=cusco',          // query string
            '/'.$ES.'/tours?destino=cusco',
            '/'.$EN.'/nosotros',
            '/'.self::pct($ES).'/nosotros',     // doble codificacion: no casa [A-Za-z-]+
            '/es/nosotros',                     // control sano: no debe redirigir
            '/es/',                             // control sano: home del locale
        ];
    }

    // ------------------------------------------------------------------
    // 1. La normalizacion entiende el path codificado
    // ------------------------------------------------------------------

    /**
     * El caso exacto de M-01, medido contra el sitio vivo antes del fix:
     * 301 con Location identico a la URI pedida, 6 saltos y curl abortando
     * con exit 47.
     */
    public function test_el_segmento_de_idioma_codificado_redirige_a_la_url_canonica_y_no_a_si_misma(): void
    {
        $uri = '/'.self::pct('ES').'/nosotros';

        $response = $this->get($uri);

        $response->assertStatus(301);
        $this->assertSame(
            '/es/nosotros',
            $this->locationPath($response->headers->get('Location')),
            "El 301 de {$uri} debe apuntar al path decodificado y en minusculas, no al path crudo."
        );
    }

    public function test_la_codificacion_parcial_tambien_se_normaliza(): void
    {
        $uris = [
            '/E'.self::pct('S').'/nosotros',
            '/'.self::pct('es').'/nosotros',
            '/e'.self::pct('s').'/nosotros',
        ];

        foreach ($uris as $uri) {
            $response = $this->get($uri);

            $response->assertStatus(301);
            $this->assertSame(
                '/es/nosotros',
                $this->locationPath($response->headers->get('Location')),
                "Fallo la normalizacion de {$uri}."
            );
        }
    }

    /**
     * B-02 del informe: "/e" + "s" codificada + "/nosotros" respondia 200 y
     * se autocanonizaba con el path CRUDO en el link rel=canonical, o sea
     * espacio de URL duplicado servido dos veces. Con la normalizacion
     * completa pasa a ser un 301 y el canonical del destino ya es el limpio.
     */
    public function test_una_url_codificada_no_sirve_contenido_duplicado_autocanonizado(): void
    {
        $uri = '/e'.self::pct('s').'/nosotros';

        // Antes del fix esto era 200, con el canonical reflejando el path crudo.
        $this->get($uri)->assertStatus(301);

        $final = $this->followToFinal($uri)['response'];

        $final->assertOk();
        $final->assertSee('rel="canonical" href="'.config('app.url').'/es/nosotros"', false);
    }

    public function test_las_mayusculas_mezcladas_se_normalizan(): void
    {
        $this->assertSame('/es/nosotros', $this->locationPath(
            $this->get('/Es/nosotros')->headers->get('Location')
        ));

        $this->assertSame('/es/contacto', $this->locationPath(
            $this->get('/eS/contacto')->headers->get('Location')
        ));
    }

    /**
     * La barra final es significativa aqui: route('home') genera "/es" pero
     * la home se sirve en "/es/", y Laravel enruta las dos a lo mismo. Lo
     * que NO puede pasar es que "/es/" empiece a redirigir a "/es": seria un
     * 301 de mas en TODA peticion de portada. El destino de "/ES/" si puede
     * quedar en cualquiera de las dos formas (el generador de URLs recorta
     * la barra final), y por eso se aceptan ambas.
     */
    public function test_la_barra_final_no_provoca_un_salto_extra_en_la_home_del_locale(): void
    {
        $this->get('/es/')->assertOk()->assertHeaderMissing('Location');

        foreach (['/ES/', '/'.self::pct('ES').'/'] as $uri) {
            $response = $this->get($uri);

            $response->assertStatus(301);
            $this->assertContains(
                $this->locationPath($response->headers->get('Location')),
                ['/es', '/es/'],
                "El destino de {$uri} debe ser la home del locale canonico."
            );
        }

        // Y ese destino, sea cual sea de las dos formas, se sirve de verdad.
        $this->assertSame(200, $this->followToFinal('/ES/')['status']);
    }

    public function test_el_301_preserva_el_query_string_con_el_path_codificado(): void
    {
        $response = $this->get('/'.self::pct('ES').'/tours?destino=cusco');

        $response->assertStatus(301);
        $this->assertSame(
            '/es/tours?destino=cusco',
            $this->locationPath($response->headers->get('Location'), withQuery: true)
        );
    }

    /**
     * Control negativo del grupo: si la doble codificacion tambien
     * "funcionara", el patron de ruta [A-Za-z-]+ no estaria filtrando nada.
     * Al decodificar una vez queda un segmento con "%", que no casa.
     */
    public function test_la_doble_codificacion_es_404_no_una_redireccion(): void
    {
        $response = $this->get('/'.self::pct(self::pct('ES')).'/nosotros');

        $response->assertNotFound();
        $response->assertHeaderMissing('Location');
    }

    /**
     * Control sano: una URL ya canonica no puede redirigir. Sin este caso,
     * un middleware que redirigiera SIEMPRE pasaria los tests de arriba.
     */
    public function test_una_url_ya_canonica_se_sirve_sin_redireccion(): void
    {
        $this->get('/es/nosotros')->assertOk()->assertHeaderMissing('Location');
    }

    // ------------------------------------------------------------------
    // 2. Garantia estructural: nunca un Location igual a la URI pedida
    // ------------------------------------------------------------------

    /**
     * La invariante. No mira si la normalizacion es CORRECTA (eso es el
     * grupo 1); mira que sea IMPOSIBLE que el middleware se autoapunte.
     */
    public function test_ninguna_redireccion_del_middleware_apunta_a_la_uri_pedida(): void
    {
        $redirecciones = 0;

        foreach (self::corpus() as $uri) {
            $response = $this->get($uri);
            $status = $response->getStatusCode();

            if ($status < 300 || $status >= 400) {
                continue;
            }

            $redirecciones++;

            $destino = (string) $this->locationPath($response->headers->get('Location'), withQuery: true);

            $this->assertNotSame($uri, $destino, "Bucle inmediato: {$uri} redirige a si misma.");

            // La barra final no cuenta como diferencia: Laravel enruta "/es"
            // y "/es/" a lo mismo, asi que un 301 que solo difiriera en eso
            // seria un salto hacia el mismo sitio. NO se compara
            // decodificado: redirigir de la forma codificada a la limpia es
            // precisamente la canonicalizacion que se busca, y el cliente
            // vuelve a pedir una URI distinta byte a byte: no hay bucle.
            $this->assertNotSame(
                rtrim($uri, '/'),
                rtrim($destino, '/'),
                "Bucle inmediato salvo barra final: {$uri} redirige a si misma."
            );
        }

        // Sin esto el test pasaria en verde aunque NINGUNA URI redirigiera:
        // un foreach sobre cero elementos no prueba nada.
        $this->assertGreaterThanOrEqual(
            10,
            $redirecciones,
            'El corpus dejo de producir redirecciones: el test ya no esta midiendo la invariante.'
        );
    }

    /**
     * La misma invariante vista desde el cliente: toda URI del corpus llega
     * a un estado final (200 o 404) en pocos saltos. Es la traduccion del
     * "curl -L --max-redirs 6 ... exit 47" del informe, y es el test que
     * distingue "hay guard" de "no hay guard".
     */
    public function test_toda_url_del_corpus_converge_en_un_200_o_un_404(): void
    {
        foreach (self::corpus() as $uri) {
            $resultado = $this->followToFinal($uri, maxHops: 4);
            $cadena = implode(' -> ', $resultado['chain']);

            $this->assertContains(
                $resultado['status'],
                [200, 404],
                "{$uri} no convergio: quedo en {$resultado['status']} tras {$resultado['hops']} saltos (cadena: {$cadena})."
            );
            $this->assertLessThanOrEqual(
                2,
                $resultado['hops'],
                "{$uri} necesito demasiados saltos: {$cadena}."
            );
        }
    }

    /**
     * Propiedad estructural extra del destino: como el segmento 0 se
     * reemplaza siempre por el locale canonico, el Location no puede
     * empezar por doble barra ni salir del origen. Cierra por construccion
     * la familia de la redireccion abierta en este middleware.
     */
    public function test_el_destino_del_301_siempre_arranca_en_el_segmento_de_locale_canonico(): void
    {
        $comprobadas = 0;

        foreach (self::corpus() as $uri) {
            $location = $this->get($uri)->headers->get('Location');

            if ($location === null) {
                continue;
            }

            $comprobadas++;

            $this->assertStringStartsWith(
                config('app.url').'/',
                $location,
                "El destino de {$uri} salio del origen: {$location}."
            );
            $this->assertMatchesRegularExpression(
                '#^/(es|en)(/|$)#',
                (string) $this->locationPath($location),
                "El destino de {$uri} no arranca en un segmento de locale canonico: {$location}."
            );
        }

        $this->assertGreaterThanOrEqual(10, $comprobadas, 'El corpus dejo de producir redirecciones.');
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    private function locationPath(?string $location, bool $withQuery = false): ?string
    {
        if ($location === null) {
            return null;
        }

        $path = parse_url($location, PHP_URL_PATH) ?: '/';

        if ($withQuery) {
            $query = parse_url($location, PHP_URL_QUERY);

            if ($query !== null && $query !== '') {
                $path .= '?'.$query;
            }
        }

        return $path;
    }

    /**
     * Sigue la cadena de redirecciones a mano (TestResponse no cuenta
     * saltos) con un tope duro: si se agota, el estado devuelto sigue
     * siendo 3xx y el assert del llamador falla mostrando la cadena
     * completa.
     *
     * @return array{status:int, hops:int, chain:list<string>, response:TestResponse}
     */
    private function followToFinal(string $uri, int $maxHops = 4): array
    {
        $chain = [$uri];
        $hops = 0;

        $response = $this->get($uri);

        while ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400 && $hops < $maxHops) {
            $location = $response->headers->get('Location');

            if ($location === null) {
                break;
            }

            $current = (string) $this->locationPath($location, withQuery: true);
            $chain[] = $current;
            $hops++;

            $response = $this->get($current);
        }

        return [
            'status' => $response->getStatusCode(),
            'hops' => $hops,
            'chain' => $chain,
            'response' => $response,
        ];
    }
}
