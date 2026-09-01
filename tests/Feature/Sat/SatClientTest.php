<?php

use App\Models\SatCredential;
use App\Models\SatTransaction;
use App\Models\User;
use App\Services\Sat\Exceptions\SatInvalidResponseException;
use App\Services\Sat\Exceptions\SatNotImplementedException;
use App\Services\Sat\Exceptions\SatUnavailableException;
use App\Services\Sat\SatClientFactory;
use App\Services\Sat\SatEndpoint;
use Illuminate\Support\Facades\Http;
use Tests\Support\SatFake;

function satClient(?SatCredential $credential = null, ?User $user = null): App\Services\Sat\SatClient
{
    $credential ??= SatCredential::factory()->create([
        'nit' => '28593111',
        'password' => 'clave-secreta',
    ]);

    return app(SatClientFactory::class)->forCredential($credential, $user);
}

it('envía usuario, contraseña y respuestaXml al endpoint correcto', function () {
    Http::fake([SatFake::url('validarNit') => Http::response(SatFake::exito())]);

    $response = satClient()->validarNit('12345678');

    expect($response->isSuccess())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === SatFake::url('validarNit')
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $request['usuario'] === '28593111'
            && $request['password'] === 'clave-secreta'
            && $request['respuestaXml'] === 'false'
            && $request['nit'] === '12345678';
    });
});

it('usa la URL del entorno y siempre termina en barra', function () {
    // La SAT solo tiene farm3 accesible; prefarm3, que aparece en sus
    // formularios de ejemplo, responde 403.
    expect(config('sat.base_url'))
        ->toBe('https://farm3.sat.gob.gt/manifiestos/rest/receptorCuscar/');
});

it('envía procesamientoSincrono en false al ingresar un cuscar', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    satClient()->ingresarCuscar('P0011234.123', 'UNB+UNOA');

    Http::assertSent(fn ($request) => $request['procesamientoSincrono'] === 'false'
        && $request['nombreArchivo'] === 'P0011234.123'
        && $request['contenidoArchivo'] === 'UNB+UNOA');
});

it('nunca guarda la contraseña de la SAT en el historial', function () {
    Http::fake([SatFake::url('validarNit') => Http::response(SatFake::exito())]);

    satClient()->validarNit('12345678');

    $transaction = SatTransaction::sole();

    expect($transaction->request_payload['password'])->toBe('***')
        ->and(json_encode($transaction->request_payload))->not->toContain('clave-secreta');
});

it('guarda el tamaño del cuscar en vez de su contenido completo', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    satClient()->ingresarCuscar('P0011234.123', str_repeat('A', 5000));

    expect(SatTransaction::sole()->request_payload['contenidoArchivo'])->toBe('<5000 bytes>');
});

it('registra la transacción con los datos de la respuesta', function () {
    $user = User::factory()->create();

    Http::fake([
        SatFake::url('consultarEncabezadoManifiesto') => Http::response(
            SatFake::exito(['numeroManifiesto' => 'GT-2026-001', 'estado' => 'RECIBIDO']),
        ),
    ]);

    $response = satClient(user: $user)->consultarEncabezadoManifiesto('GT-2026-001');

    $transaction = SatTransaction::sole();

    expect($transaction->endpoint)->toBe(SatEndpoint::ConsultarEncabezadoManifiesto)
        ->and($transaction->succeeded)->toBeTrue()
        ->and($transaction->http_status)->toBe(200)
        ->and($transaction->user_id)->toBe($user->id)
        ->and($transaction->response_manifiesto['numeroManifiesto'])->toBe('GT-2026-001')
        ->and($transaction->uuid)->toBe($response->transactionUuid)
        ->and($transaction->duration_ms)->not->toBeNull();
});

it('marca como fallida la transacción cuando la SAT rechaza la operación', function () {
    Http::fake([SatFake::url('validarNit') => Http::response(SatFake::error('El NIT no existe'))]);

    $response = satClient()->validarNit('00000000');

    expect($response->isSuccess())->toBeFalse()
        ->and($response->descripcion)->toBe('El NIT no existe')
        ->and(SatTransaction::sole()->succeeded)->toBeFalse();
});

it('trata una página HTML con estado 200 como respuesta inválida y deja rastro', function () {
    Http::fake([SatFake::url('validarNit') => SatFake::htmlConEstado200()]);

    expect(fn () => satClient()->validarNit('12345678'))
        ->toThrow(SatInvalidResponseException::class);

    $transaction = SatTransaction::sole();

    expect($transaction->succeeded)->toBeFalse()
        ->and($transaction->error_class)->toBe('SatInvalidResponseException')
        ->and($transaction->response_raw)->toContain('Mantenimiento');
});

it('trata un XML como respuesta inválida', function () {
    Http::fake([SatFake::url('validarNit') => SatFake::xml()]);

    expect(fn () => satClient()->validarNit('12345678'))
        ->toThrow(SatInvalidResponseException::class);
});

it('trata un 403 con HTML como servicio no disponible y guarda el cuerpo', function () {
    Http::fake([SatFake::url('validarNit') => SatFake::html(403)]);

    expect(fn () => satClient()->validarNit('12345678'))
        ->toThrow(SatUnavailableException::class);

    $transaction = SatTransaction::sole();

    expect($transaction->http_status)->toBe(403)
        ->and($transaction->error_class)->toBe('SatUnavailableException')
        ->and($transaction->response_raw)->toContain('Acceso denegado');
});

it('reintenta cuando la conexión falla y registra los intentos', function () {
    Http::fake([SatFake::url('validarNit') => fn () => throw SatFake::timeout()]);

    expect(fn () => satClient()->validarNit('12345678'))
        ->toThrow(SatUnavailableException::class);

    // Http::assertSentCount no sirve aquí: las peticiones que terminan en
    // excepción no quedan registradas. El contador de la transacción sí.
    expect(SatTransaction::sole()->attempts)->toBe(config('sat.retry.times'));
});

it('no reintenta el envío de un cuscar, que no es idempotente', function () {
    Http::fake([SatFake::url('ingresarCuscar') => fn () => throw SatFake::timeout()]);

    expect(fn () => satClient()->ingresarCuscar('P0011234.123', 'UNB'))
        ->toThrow(SatUnavailableException::class);

    // Un reintento podría dar de alta el mismo manifiesto dos veces.
    expect(SatTransaction::sole()->attempts)->toBe(1);
});

it('no reintenta ante un error del servidor de la SAT', function () {
    Http::fake([SatFake::url('validarNit') => Http::response('Error interno', 500)]);

    expect(fn () => satClient()->validarNit('12345678'))
        ->toThrow(SatUnavailableException::class);

    Http::assertSentCount(1);
});

it('rechaza los endpoints que todavía no están implementados', function () {
    expect(fn () => satClient()->solicitarCuscar())
        ->toThrow(SatNotImplementedException::class);

    expect(SatTransaction::count())->toBe(0);
});
