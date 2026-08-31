<?php

use App\Services\Sat\SatHealthCheck;
use Illuminate\Support\Facades\Http;
use Tests\Support\SatFake;

it('reconoce el servicio activo por su respuesta en texto plano', function () {
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);

    expect(app(SatHealthCheck::class)->isUp())->toBeTrue();
});

it('reporta caído cuando la SAT devuelve una página de error', function () {
    Http::fake([SatFake::url('probar') => SatFake::html(403)]);

    expect(app(SatHealthCheck::class)->isUp())->toBeFalse();
});

it('no propaga la excepción cuando no hay conexión', function () {
    Http::fake([SatFake::url('probar') => fn () => throw SatFake::timeout()]);

    expect(app(SatHealthCheck::class)->isUp())->toBeFalse();
});

it('cachea el resultado para no consultar en cada carga de pantalla', function () {
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);

    $health = app(SatHealthCheck::class);
    $health->isUp();
    $health->isUp();
    $health->isUp();

    Http::assertSentCount(1);
});
