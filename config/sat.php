<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    |
    | La SAT solo tiene un ambiente accesible: farm3. El host prefarm3 que
    | aparece en los formularios de ejemplo entregados por la SAT (sat/*.html
    | del sistema legacy) responde 403 y nunca fue usado por el código en
    | producción, que siempre habló con farm3.
    |
    | Cambiar de ambiente sigue siendo una variable de entorno y nunca una
    | edición de código: en el sistema legacy la URL estaba escrita a mano en
    | nueve lugares distintos.
    |
    */

    'environment' => env('SAT_ENVIRONMENT', 'produccion'),

    'base_url' => rtrim(env(
        'SAT_BASE_URL',
        'https://farm3.sat.gob.gt/manifiestos/rest/receptorCuscar/',
    ), '/').'/',

    // Documentado por la SAT pero inaccesible: devuelve 403.
    'preproduction_url' => 'https://prefarm3.sat.gob.gt/manifiestos/rest/receptorCuscar/',

    /*
    |--------------------------------------------------------------------------
    | Cliente HTTP
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('SAT_TIMEOUT', 30),
    'connect_timeout' => (int) env('SAT_CONNECT_TIMEOUT', 10),

    'retry' => [
        'times' => (int) env('SAT_RETRY_TIMES', 2),
        'sleep_ms' => (int) env('SAT_RETRY_SLEEP_MS', 500),
    ],

    'verify_ssl' => (bool) env('SAT_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Verificación de disponibilidad
    |--------------------------------------------------------------------------
    |
    | El endpoint `probar` devuelve texto plano. Se consulta con un timeout corto
    | y el resultado se cachea, para que una caída de la SAT no bloquee cada
    | carga de pantalla como ocurría con el file_get_contents del sistema viejo.
    |
    */

    'healthcheck' => [
        'ttl' => (int) env('SAT_HEALTHCHECK_TTL', 60),
        'timeout' => (int) env('SAT_HEALTHCHECK_TIMEOUT', 3),
        'expected_body' => 'Servicio web activo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Archivos cuscar
    |--------------------------------------------------------------------------
    */

    'cuscar' => [
        'disk' => 'cuscar',
        'max_bytes' => (int) env('SAT_CUSCAR_MAX_BYTES', 1_240_000),
        // Cómo tratar los saltos de línea antes de transmitir:
        //   crlf     reproduce la cadena del navegador del sistema legacy, cuyo
        //            efecto neto es transmitir un archivo CRLF con sus saltos
        //            intactos: es lo que la SAT viene aceptando desde hace años.
        //   todos    quita CRLF, LF y CR, dejando los segmentos pegados.
        //   ninguno  transmite el archivo tal cual.
        'newline_mode' => env('SAT_CUSCAR_NEWLINE_MODE', 'crlf'),
        'service_types' => ['P', 'E'],
        'procesamiento_sincrono' => env('SAT_PROCESAMIENTO_SINCRONO', false) ? 'true' : 'false',
        // La SAT no valida al instante; este es el tiempo que se le indica al
        // usuario que espere antes de consultar los errores del cuscar.
        'validation_delay_seconds' => 180,
        'preview_lines' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Registro de transacciones
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'store_raw_response' => (bool) env('SAT_STORE_RAW_RESPONSE', true),
        'raw_max_chars' => (int) env('SAT_RAW_RESPONSE_MAX_CHARS', 20000),
        'redacted_keys' => ['password'],
    ],

];
