<?php

use App\Enums\CuscarStatus;
use App\Models\CuscarFile;
use App\Models\SatCredential;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SatFake;

function operadorValidador(): User
{
    $user = User::factory()->operador()->create();
    SatCredential::factory()->create()->users()->attach($user, ['assigned_at' => now()]);

    return $user;
}

beforeEach(function () {
    Storage::fake('cuscar');
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);
});

it('avisa de la espera cuando se llega desde el envío', function () {
    $this->actingAs(operadorValidador())
        ->withSession(['recien_enviado' => true])
        ->get(route('sat.cuscar.validar.create', ['nombreArchivo' => 'P0011234.123']))
        ->assertOk()
        ->assertSee('Espere 3 minutos antes de validar')
        ->assertSee('P0011234.123');
});

it('no muestra el aviso de espera en una consulta normal', function () {
    $this->actingAs(operadorValidador())
        ->get(route('sat.cuscar.validar.create'))
        ->assertOk()
        ->assertDontSee('Espere 3 minutos');
});

it('marca el archivo como aceptado y guarda el número de manifiesto', function () {
    Http::fake([
        SatFake::url('consultarErroresCuscar') => Http::response(SatFake::exito([
            'nombreCuscar' => 'P0011234.123',
            'numeroManifiesto' => 'GT-2026-555',
            'fechaRecepcion' => '2026-08-31 11:00:00',
        ], 'Archivo procesado sin errores')),
    ]);

    $user = operadorValidador();
    $file = CuscarFile::factory()->for($user)->enviado()->create(['filename' => 'P0011234.123']);

    $this->actingAs($user)
        ->post(route('sat.cuscar.validar.store'), ['nombreArchivo' => 'P0011234.123'])
        ->assertRedirect(route('sat.cuscar.validar.show', $file));

    $file->refresh();

    expect($file->status)->toBe(CuscarStatus::Aceptado)
        ->and($file->numero_manifiesto)->toBe('GT-2026-555')
        ->and($file->fecha_recepcion)->toBe('2026-08-31 11:00:00');
});

it('marca el archivo como rechazado cuando la SAT reporta errores', function () {
    Http::fake([
        SatFake::url('consultarErroresCuscar') => Http::response(
            SatFake::error('El archivo contiene 4 errores de estructura'),
        ),
    ]);

    $user = operadorValidador();
    $file = CuscarFile::factory()->for($user)->enviado()->create(['filename' => 'P0011234.123']);

    $this->actingAs($user)
        ->post(route('sat.cuscar.validar.store'), ['nombreArchivo' => 'P0011234.123'])
        ->assertSessionHas('sat_error', 'El archivo contiene 4 errores de estructura');

    expect($file->refresh()->status)->toBe(CuscarStatus::Rechazado)
        ->and($file->last_response_description)->toBe('El archivo contiene 4 errores de estructura');
});

it('muestra el resultado aunque el cuscar no se haya subido desde este sistema', function () {
    Http::fake([
        SatFake::url('consultarErroresCuscar') => Http::response(SatFake::exito([
            'numeroManifiesto' => 'GT-EXTERNO',
        ])),
    ]);

    $this->actingAs(operadorValidador())
        ->post(route('sat.cuscar.validar.store'), ['nombreArchivo' => 'E9995678.001'])
        ->assertRedirect()
        ->assertSessionHas('sat_result.exito', true);

    expect(CuscarFile::count())->toBe(0);
});

it('valida el formato del nombre antes de llamar a la SAT', function () {
    $this->actingAs(operadorValidador())
        ->post(route('sat.cuscar.validar.store'), ['nombreArchivo' => 'X001ABCD.zz'])
        ->assertSessionHasErrors('nombreArchivo');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'consultarErroresCuscar'));
});

it('solo actualiza archivos del propio usuario', function () {
    Http::fake([SatFake::url('consultarErroresCuscar') => Http::response(SatFake::exito())]);

    $ajeno = CuscarFile::factory()->for(operadorValidador())->enviado()->create(['filename' => 'P0011234.123']);

    // Otro usuario consulta el mismo nombre: el archivo ajeno no debe cambiar.
    $this->actingAs(operadorValidador())
        ->post(route('sat.cuscar.validar.store'), ['nombreArchivo' => 'P0011234.123']);

    expect($ajeno->refresh()->status)->toBe(CuscarStatus::Enviado);
});

it('impide ver el resultado de un archivo ajeno', function () {
    $file = CuscarFile::factory()->for(operadorValidador())->create();

    $this->actingAs(operadorValidador())
        ->get(route('sat.cuscar.validar.show', $file))
        ->assertForbidden();
});
