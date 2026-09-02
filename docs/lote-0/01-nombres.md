# Lote 0 — Nombres con dominio libre

Fecha de verificación: **01/09/2026**. Los dominios se compran o se pierden: reverificar antes
de comprar.

## Por qué cambiar "Perú Local"

- **`perulocal.com` está registrado** (verificado, ver método abajo). El nombre actual no tiene
  ni el dominio exacto.
- Es una descripción, no una marca: dos palabras genéricas que compiten en buscadores con todo
  lo que se publica sobre turismo en Perú, y que difícilmente se registran como marca.

## Método de verificación (y qué NO verifica)

| Qué | Cómo | Confiable |
|---|---|---|
| `.com` | RDAP de Verisign: HTTP 404 = libre, 200 = registrado | **Sí.** Control probado: `google.com` → 200, `qwxzplk48273nada.com` → 404 |
| `.pe` / `.com.pe` | **No verificable.** `.pe` no publica RDAP: `rdap.org` devuelve 404 tanto para `google.pe` como para un dominio inventado, así que el chequeo no distingue nada | **No.** Solo hay señal DNS: "sin registros NS" ≠ libre. Hay que consultar en NIC.pe a mano |
| Colisión de marca | **No hecho.** Falta buscar en INDECOPI, en redes sociales y en agencias existentes | **No.** Obligatorio antes de comprar |

## Candidatos

Ordenados por recomendación. "DNS .pe" es solo señal, no disponibilidad.

| # | Nombre | `.com` exacto | Alternativa `.com` | DNS `.pe` / `.com.pe` | Lectura |
|---|---|---|---|---|---|
| 1 | **Pacha Viva** | `pachaviva.com` **LIBRE** | `pachavivaperu.com` libre | sin NS / sin NS | *Pacha* (tierra/tiempo, quechua) + *viva*. 9 letras, sin acentos ni ñ, se lee igual en ES, EN y PT — importa porque en el lote 5 entran EN y PT |
| 2 | **Chaska** | `chaska.com` tomado | `chaskatravel.com` y `chaskaviajes.com` **LIBRES** | sin NS / sin NS | *Chaska* = estrella/lucero. Bonito y corto. **Riesgo:** Chaska es también una ciudad de Minnesota (EE.UU.), competencia directa en búsquedas en inglés |
| 3 | **Rumbo Andino** | `rumboandino.com` **LIBRE** | — | sin NS / sin NS | Claro e inmediato en español. Flojo en EN/PT y demasiado descriptivo para registrar como marca |
| 4 | **Rikuy** | `rikuy.com` tomado | `rikuyperu.com` **LIBRE** | sin NS / sin NS | *Rikuy* = mirar/ver. Difícil de pronunciar y de deletrear para un angloparlante |
| 5 | Andes Cercanos | `andescercanos.com` **LIBRE** | — | sin NS / sin NS | 13 caracteres y no viaja a EN/PT |
| — | Wasi | tomado | `wasitravel.com` libre | resuelve NS / resuelve NS | **Descartado:** `wasi.co` es un CRM inmobiliario conocido en la región |
| — | Taripay | tomado | `taripaytravel.com` libre | sin NS / **resuelve NS** | **Descartado:** el `.com` de marca no está y `taripay.com.pe` ya tiene DNS |

También verificados y **registrados**: `aynitravel.com`, `ayniperu.com`, `puriy.com`,
`puriytravel.com`, `tinkuy.com`, `tinkuyperu.com`, `wayraperu.com`, `killaperu.com`,
`muyutravel.com`, `rutapropia.com`, `sonqotravel.com`, `vivepacha.com`.

## Recomendación

**Pacha Viva**, con `pachaviva.com` (exacto, libre) y `pachavivaperu.com` como defensivo.
Es el único candidato que tiene el `.com` exacto libre, se escribe sin ambigüedad en los tres
idiomas del proyecto y tiene raíz andina real sin ser un término que ya usen veinte agencias.

## Antes de comprar — pendiente y bloqueante

1. Buscar la marca en **INDECOPI** (clase 39, servicios de viaje) y en agencias existentes.
2. Verificar `.pe` y `.com.pe` en **NIC.pe** a mano (no hay forma automática).
3. Verificar que los usuarios de Instagram, Facebook y TikTok estén libres.
4. Que la clienta lo apruebe: el nombre es suyo, no nuestro.

---

# Ronda 2 — la prueba de colisión, que faltaba (01/09/2026, noche)

## Corrección al criterio de la ronda 1

La ronda 1 filtró **solo por dominio libre** y dejó la búsqueda de colisión como pendiente.
Hecha esa búsqueda, la recomendación se debilita: **un `.com` libre no significa que el nombre
esté libre.** El criterio correcto tiene dos ejes y ambos son eliminatorios:

1. **Dominio** — verificable por RDAP, ya resuelto.
2. **Colisión en el sector** — si el nombre ya lo usan agencias de turismo peruanas, el `.com`
   libre no sirve de nada: la marca no se distingue, el SEO pelea contra ellas y en INDECOPI
   un nombre compuesto de términos comunes del rubro es difícil de registrar en exclusiva.

## Qué encontró la búsqueda

| Candidato de la ronda 1 | Colisión en turismo peruano | Veredicto |
|---|---|---|
| **Pacha Viva** | "Pacha" está muy ocupado: Pacha Trip Perú, Pacha Expeditions (Cusco, 2021), Pacha Tours, Illay Pacha Travel, Inka Pacha. "Viva" también: Viva Tour Machupicchu, Viva Cusco | **Debilitado.** Es mejor que "Perú Local", pero comparte su defecto: dos palabras ya comunes en el rubro |
| **Chaska** | Peor: Chaska Tours (Cusco, ~22 años), Chaska Travel Perú, Chaska Andina Travel, `chaskatours.com` | **Descartado** |
| **Rumbo Andino** | Existe **Rumbo Andino Perú S.A.C.S.** (Lima, Jesús María, operando desde julio 2025) aunque el `.com` esté libre | **Descartado** por colisión de razón social |
| Rikuy | Sin colisión encontrada en turismo | sigue en pie, con su problema de pronunciación en inglés |

Nota de método: un resultado de búsqueda mostraba `rumboandino.com` como sitio vivo. Contrastado
con tres mediciones —RDAP 404, NXDOMAIN en DNS y sin respuesta HTTP— **el dominio está libre**;
el resultado estaba desactualizado. Lo que existe es la empresa, no el sitio.

## Ronda 2 de candidatos, filtrados por los dos ejes

Descartados en el camino, con su motivo:

- `ichuperu.com` / `ichutravel.com` libres, pero **Ichu Perú es el restaurante de Virgilio
  Martínez** (Hong Kong, Dubái). Colisión con una marca peruana de alto perfil. **Fuera.**
- Familia **Puna** (`casapuna.com`, `puntopuna.com`, `punaalta.com` libres): ya hay `puna.com.pe`
  y una "Puna Travel" fundada en 2025. Token ocupado en el rubro. **Fuera.**
- Quechua corto (`kuska`, `wayta`, `sisa`, `tarpu`, `urpi`, `rimay`, `willka`, `paqcha`, `puriq`,
  `suyay`, `pukyu`, `chirapa`, `tunki`, `taruka`, `wayruro`, `chuya`): **todos tomados** en `.com`
  exacto. Solo quedan libres los compuestos con "travel", que devuelven el nombre a lo genérico.

Lo que sobrevive a ambos filtros:

| Nombre | Dominios libres | Colisión en turismo | Punto débil |
|---|---|---|---|
| **Chuya** (quechua: claro, puro, limpio) | `chuyatravel.com`, `caminochuya.com` | **ninguna encontrada** | el `.com` exacto está tomado |
| Wayruro (la semilla roja y negra de amuleto) | `wayrurotravel.com` | no verificada a fondo; hay artesanía con ese nombre | `.com` exacto tomado |
| Taruka (venado andino) | `tarukatravel.com` | no verificada a fondo | `.com` exacto tomado |
| Nudo Andino | `nudoandino.com` | — | "andino" es justamente un término saturado del rubro |

## Lo que esto significa para la decisión

El requisito de **`.com` exacto** es lo que fuerza el problema: obliga a nombres compuestos o
genéricos, que son los que colisionan. Hay dos salidas y las dos son legítimas:

1. **Aceptar un `.com` compuesto** (`chuyatravel.com`) con un nombre distintivo detrás.
2. **Usar `.pe` como dominio principal**, que para una agencia peruana es perfectamente normal y
   libera los nombres cortos. Requiere verificar en **NIC.pe a mano**: no hay forma automática.

**Pacha Viva sigue siendo usable** — no está tomado por nadie con ese nombre exacto — pero ya no
lo recomiendo como primera opción sin que Anyerson vea esta tabla. La decisión es suya.

## Sigue pendiente y sigue bloqueando la compra

Búsqueda en **INDECOPI** clase 39 (esto fue una búsqueda web, no una consulta al registro),
verificación de `.pe` y `.com.pe` en **NIC.pe** a mano, usuarios de redes, y aprobación de la
clienta.
