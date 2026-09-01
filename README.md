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
