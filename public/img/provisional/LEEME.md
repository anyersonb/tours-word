# Imagenes provisionales

Todas las imagenes de esta carpeta (y `public/img/brand/inka-palace-logo.png`) son **recortes del PDF de diseno**
(`Inka-Palace-1-Home.pdf`, una sola imagen JPEG de 2880 x 18142 px). **NO son aptas para produccion.**

- Resolucion baja: el hero a pantalla completa tiene ~1440x798 px de origen; `hero-ica.jpg` y `hero-machu-picchu.jpg`
  son recortes pequenos (~330-500 px de ancho) estirados con `object-fit: cover`: se veran borrosos.
- Algunas tarjetas llevan zonas con texto/botones del PDF borradas por interpolacion (manchas oscuras bajo el overlay).
  En `hero-lima.jpg` las zonas de texto y tarjetas del diseno estan difuminadas; el contenido real las tapa.
- El logo es un recorte del pie del PDF (~216x168 px, fondo blanco). Hace falta SVG/AI de la clienta.
- Varias fotos muestran personas identificables (clientes reales): se necesitan originales con derechos de uso.
- Los nombres de archivo son contrato con `config/brand.php`: al llegar los originales, reemplazar manteniendo nombre y proporcion.
