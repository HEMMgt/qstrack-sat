<?php

namespace Tests\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Cuerpos de respuesta de la SAT para las pruebas, incluidos los formatos rotos
 * que devuelve en la práctica.
 */
final class SatFake
{
    /**
     * @param  array<string, string>  $manifiesto
     * @return array<string, mixed>
     */
    public static function exito(array $manifiesto = [], string $descripcion = 'Operación realizada con éxito'): array
    {
        return [
            'manifiestoRespuesta' => [
                'respuesta' => ['tipo' => 'EXITO', 'descripcion' => $descripcion],
                'manifiesto' => $manifiesto + self::emptyManifiesto(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function error(string $descripcion = 'El NIT no existe', string $tipo = 'ERROR'): array
    {
        return [
            'manifiestoRespuesta' => [
                'respuesta' => ['tipo' => $tipo, 'descripcion' => $descripcion],
                'manifiesto' => self::emptyManifiesto(),
            ],
        ];
    }

    /** Página de error HTML: lo que devuelve prefarm3 hoy con un 403. */
    public static function html(int $status = 403): PromiseInterface
    {
        return Http::response(
            '<HTML><HEAD><TITLE>SAT</TITLE></HEAD><BODY>Acceso denegado</BODY></HTML>',
            $status,
            ['Content-Type' => 'text/html'],
        );
    }

    /** 200 con HTML: el peor caso, porque parece exitoso. */
    public static function htmlConEstado200(): PromiseInterface
    {
        return Http::response('<HTML><BODY>Mantenimiento</BODY></HTML>', 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    public static function xml(): PromiseInterface
    {
        return Http::response(
            '<?xml version="1.0"?><manifiestoRespuesta><respuesta><tipo>EXITO</tipo></respuesta></manifiestoRespuesta>',
            200,
            ['Content-Type' => 'application/xml'],
        );
    }

    public static function timeout(): ConnectionException
    {
        return new ConnectionException('cURL error 28: Operation timed out');
    }

    /**
     * @return array<string, string>
     */
    private static function emptyManifiesto(): array
    {
        return [
            'nombreCuscar' => '',
            'numeroManifiesto' => '',
            'fechaRecepcion' => '',
            'firmaElectronica' => '',
            'tipoMensaje' => '',
            'funcionMensaje' => '',
            'estado' => '',
            'estadoDictamen' => '',
            'tipoOperacion' => '',
            'empresaTransmisora' => '',
            'numeroViajeVuelo' => '',
            'nombreMedioTransporte' => '',
        ];
    }

    /** URL absoluta del endpoint, para Http::fake() y las aserciones. */
    public static function url(string $endpoint): string
    {
        return config('sat.base_url').$endpoint;
    }
}
