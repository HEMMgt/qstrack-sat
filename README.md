# QSTrack SAT

Módulo de integración con el servicio de manifiestos de la SAT (Superintendencia
de Administración Tributaria de Guatemala), reescrito en Laravel 12.

Reemplaza el submenú SAT del sistema legacy `qstrack` con cinco pantallas:

1. **Validar NIT** — comprueba un NIT ante la SAT.
2. **Validar cuscar** — consulta si un archivo transmitido tiene errores.
3. **Agregar cuscar** — carga, revisión y transmisión de un archivo cuscar.
4. **Consultar manifiesto** — encabezado de un manifiesto ya transmitido.
5. **Cambiar clave SAT** — rotación de la credencial propia.

## Puesta en marcha

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
mysql -h 127.0.0.1 -u root -e "CREATE DATABASE qstrack_sat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan sat:probar     # verifica el servicio y muestra el ambiente activo
```

El proyecto se sirve con Laravel Herd desde `~/Herd/qstrack-sat` como
`https://qstrack-sat.test`.

Usuarios del seeder (contraseña `password`): `admin@qstrack.test`,
`operador@qstrack.test`, `auditor@qstrack.test`.

## Ambiente de la SAT

```dotenv
SAT_ENVIRONMENT=produccion
SAT_BASE_URL=https://farm3.sat.gob.gt/manifiestos/rest/receptorCuscar/
```

**La SAT solo tiene `farm3` accesible.** El host `prefarm3` aparece en los
formularios de ejemplo que la SAT entregó (guardados como `sat/*.html` en el
sistema legacy), pero responde 403 y el código en operación nunca lo usó: los
nueve puntos donde el sistema anterior tenía la URL escrita a mano apuntan todos
a farm3.

La URL sigue siendo una variable de entorno y no una constante en el código, de
modo que si la SAT habilita un ambiente de pruebas basta con cambiarla. Tras
modificarla: `php artisan config:clear`.

Como no hay ambiente de pruebas, **toda operación es real**. `validarNit` y
`consultarEncabezadoManifiesto` son consultas de solo lectura, pero
`ingresarCuscar` da de alta un manifiesto: por eso el envío pide confirmación,
nunca se reintenta solo, y un reenvío exige marcar una casilla.

## Decisiones de diseño

**Las llamadas a la SAT salen del servidor.** El sistema legacy imprimía el NIT
y la contraseña en campos ocultos del HTML y era el navegador del usuario quien
hacía el POST a `farm3.sat.gob.gt`; cualquiera podía leer las credenciales de la
empresa viendo el código fuente de la página. Aquí todo pasa por
`App\Services\Sat\SatClient`.

**Toda llamada queda registrada.** El único camino hacia la SAT es el método
privado `SatClient::call()`, que abre la fila en `sat_transactions` antes del
request y la cierra en un `finally`. No hace falta acordarse de registrar nada en
los controladores, y un fallo también deja rastro, con el cuerpo crudo de la
respuesta para poder diagnosticar.

**Solo se reintenta por fallo de conexión.** Un 4xx o 5xx es una respuesta de la
SAT y repetirla no la cambia; en `ingresarCuscar` un reintento podría registrar
el mismo manifiesto dos veces.

**La contraseña SAT se guarda cifrada** (cast `encrypted`), nunca se muestra y se
sustituye por `***` en el historial de transacciones.

**Los archivos cuscar viven en un disco privado** (`storage/app/private/cuscar`),
no bajo `public/` como en el sistema anterior, y se descargan solo por una ruta
autenticada con su política.

**La autorización está en las rutas, no en el menú.** El menú usa `@can` solo
para no mostrar lo que no se puede usar; quien escriba la URL a mano recibe 403.

## Despliegue

El `DocumentRoot` del servidor web debe apuntar a **`public/`**, nunca a la raíz
del proyecto: de lo contrario quedan expuestos el `.env` y todo el código.

`APP_URL` tiene que llevar el esquema real con el que se sirve el sitio. Si es
`https://`, la aplicación fuerza ese esquema en todos los enlaces y assets
(`AppServiceProvider::forceHttpsWhenConfigured`), porque tras un proxy o CDN que
termina el TLS la petición llega a PHP como http plano y el navegador bloquea
los assets http dentro de una página segura: la aplicación se ve sin estilos.

La aplicación opera en `America/Guatemala`, no en UTC: la SAT devuelve las
fechas en hora local y guardar en UTC deja los registros propios seis horas
adelantados frente a lo que reporta el servicio para la misma operación.

Permisos, una sola vez y con el usuario propietario del código:

```bash
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} \;
sudo chmod 755 .          # el servidor web debe poder atravesar el directorio
```

**Nunca ejecutes `artisan`, `composer` ni `npm` con `sudo`.** Los archivos que
generen quedarán como root y ni el servidor web ni tu usuario podrán
reescribirlos; el síntoma típico es un *permission denied* sobre
`storage/logs/laravel.log` o `bootstrap/cache/config.php`.

Tras cambiar el `.env`:

```bash
php artisan config:clear && php artisan config:cache
```

## El emisor debe corresponder a la credencial

La SAT exige que el emisor declarado en el segmento `UNB` del cuscar sea la
empresa con cuyas credenciales se transmite. Enviar un manifiesto de una empresa
autenticado como otra provoca un rechazo en el segmento de cabecera cuyo mensaje
no explica la causa real.

`App\Services\Sat\Support\CuscarHeader` lee ese emisor al cargar el archivo y
se guarda en `cuscar_files.emisor`. La pantalla de revisión lo muestra junto a la
credencial que se usará, para que ambos datos se vean antes de transmitir.

Cada credencial puede llevar su **código de emisor (GLN)**, el que la SAT publica
en sus manifiestos. Cuando está capturado, el sistema impide transmitir archivos
de otro emisor. Y aunque la credencial asignada no lo tenga, si el emisor del
archivo corresponde a otra credencial registrada, el envío también se detiene
indicando cuál es la correcta.

## Cómo se transmite el contenido de un cuscar

El sistema legacy no enviaba desde PHP: descargaba el archivo en el navegador
con `jQuery.get()` y posteaba desde el cliente, de modo que el navegador hacía
por su cuenta dos transformaciones que aquí son explícitas.

`App\Services\Sat\Support\CuscarContent` las reproduce:

1. **Decodificación.** Los archivos que generan los sistemas de las navieras
   vienen en UTF-16 con marca de orden de bytes. Se convierten a texto plano; sin
   eso la SAT no logra leer ni el segmento `BGM`.
2. **Saltos de línea.** Un archivo CRLF se transmite **con sus saltos intactos**.
   El `replace(/\n/g,"")` del legacy parece eliminarlos, pero el navegador lo
   deshace: meter el texto al textarea con `.html()` hace que el parser HTML
   convierta cada CR suelto en LF, y `serializeArray()` de jQuery los devuelve
   como CRLF. La cadena completa se anula a sí misma, así que lo que la SAT
   recibe del sistema viejo es el archivo decodificado sin cambios — y su
   analizador cuenta esas líneas; sin ellas (o con CR sueltos) rechaza con
   errores de lexema y de segmento de cabecera. Configurable con
   `SAT_CUSCAR_NEWLINE_MODE` (`crlf`, `todos`, `ninguno`).
3. **Acentos.** Se transliteran (`Á→A`, `Ñ→N`, …) antes de transmitir. La
   sintaxis UNOA que declaran los archivos no los admite, y la SAT almacena el
   contenido como Latin-1: una `Ó` enviada en UTF-8 queda registrada como `Ã“`
   y así aparece en sus consultas y PDF — el legacy la envía tal cual y sufre
   esa corrupción. Configurable con `SAT_CUSCAR_TRANSLITERAR` (activado por
   omisión).

Cuando la SAT rechace un archivo, este comando dice exactamente qué se le
transmitió y lo compara contra lo que envía otro sistema:

```bash
php artisan sat:inspeccionar-cuscar ruta/al/archivo.244

# Comprueba que se transmite lo mismo que enviaría el sistema legacy
php artisan sat:inspeccionar-cuscar ruta/al/archivo.244 --simular-legacy

# O contra una captura real de otro sistema
php artisan sat:inspeccionar-cuscar ruta/al/archivo.244 --comparar=captura.txt
```

## Estructura

```
app/Services/Sat/     SatClient, factory, health check, DTOs y excepciones
app/Rules/            CuscarFileName (formato TCCCNNNN.JJJ)
app/Support/          AuditLogger
app/Enums/            Role, Permission, CuscarStatus, SatEndpoint
config/sat.php        ambiente, timeouts, reintentos, límites de cuscar
```

## Pruebas

```bash
php artisan test
```

Ninguna prueba sale a internet: `Http::preventStrayRequests()` está activo, de
modo que un `Http::fake()` olvidado hace fallar el test en vez de llamar a la SAT.
