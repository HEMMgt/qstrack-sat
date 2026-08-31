<?php

use App\Models\SatCredential;
use App\Models\SatManifest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\Support\SatFake;

function operadorSat(): User
{
    $user = User::factory()->operador()->create();
    SatCredential::factory()->create()->users()->attach($user, ['assigned_at' => now()]);

    return $user;
}

beforeEach(function () {
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);
});

it('guarda los doce campos del manifiesto que devuelve la SAT', function () {
    Http::fake([
        SatFake::url('consultarEncabezadoManifiesto') => Http::response(SatFake::exito([
            'nombreCuscar' => 'P0011234.123',
            'numeroManifiesto' => 'GT-2026-001',
            'fechaRecepcion' => '2026-08-31 10:15:00',
            'firmaElectronica' => 'ABC123FIRMA',
            'tipoMensaje' => 'CUSCAR',
            'funcionMensaje' => '9',
            'estado' => 'RECIBIDO',
            'estadoDictamen' => 'SIN DICTAMEN',
            'tipoOperacion' => 'IMPORTACION',
            'empresaTransmisora' => 'Envios Urgentes S.A.',
            'numeroViajeVuelo' => 'AV-204',
            'nombreMedioTransporte' => 'AEREO',
        ])),
    ]);

    $this->actingAs(operadorSat())
        ->post(route('sat.manifiesto.store'), ['numeroManifiesto' => 'GT-2026-001'])
        ->assertRedirect();

    $manifest = SatManifest::sole();

    expect($manifest->numero_manifiesto)->toBe('GT-2026-001')
        ->and($manifest->estado)->toBe('RECIBIDO')
        ->and($manifest->estado_dictamen)->toBe('SIN DICTAMEN')
        ->and($manifest->empresa_transmisora)->toBe('Envios Urgentes S.A.')
        ->and($manifest->numero_viaje_vuelo)->toBe('AV-204')
        ->and($manifest->nombre_medio_transporte)->toBe('AEREO')
        ->and($manifest->firma_electronica)->toBe('ABC123FIRMA')
        ->and($manifest->sat_transaction_id)->not->toBeNull();
});

it('muestra los datos del manifiesto en la pantalla de resultado', function () {
    $user = operadorSat();
    $manifest = SatManifest::factory()->for($user)->create([
        'numero_manifiesto' => 'GT-2026-777',
        'estado' => 'VALIDADO',
    ]);

    $this->actingAs($user)
        ->get(route('sat.manifiesto.show', $manifest))
        ->assertOk()
        ->assertSee('GT-2026-777')
        ->assertSee('VALIDADO')
        ->assertSee('Medio de transporte');
});

it('escapa el contenido que devuelve la SAT', function () {
    $user = operadorSat();
    $manifest = SatManifest::factory()->for($user)->create([
        'estado' => '<script>alert(1)</script>',
    ]);

    // El sistema legacy imprimía estos valores desde la URL sin escapar.
    $this->actingAs($user)
        ->get(route('sat.manifiesto.show', $manifest))
        ->assertDontSee('<script>alert(1)</script>', escape: false);
});

it('no guarda nada cuando la SAT rechaza la consulta', function () {
    Http::fake([
        SatFake::url('consultarEncabezadoManifiesto') => Http::response(
            SatFake::error('El manifiesto no existe'),
        ),
    ]);

    $this->actingAs(operadorSat())
        ->post(route('sat.manifiesto.store'), ['numeroManifiesto' => 'NOEXISTE'])
        ->assertRedirect()
        ->assertSessionHas('sat_error', 'El manifiesto no existe');

    expect(SatManifest::count())->toBe(0);
});

it('impide ver el manifiesto consultado por otro usuario', function () {
    $manifest = SatManifest::factory()->for(operadorSat())->create();

    $this->actingAs(operadorSat())
        ->get(route('sat.manifiesto.show', $manifest))
        ->assertForbidden();
});

it('permite al auditor ver cualquier manifiesto', function () {
    $manifest = SatManifest::factory()->for(operadorSat())->create();

    $this->actingAs(User::factory()->auditor()->create())
        ->get(route('sat.manifiesto.show', $manifest))
        ->assertOk();
});

it('lista solo los manifiestos propios al operador', function () {
    SatManifest::factory()->for($user = operadorSat())->create(['numero_manifiesto' => 'MIO-1']);
    SatManifest::factory()->for(operadorSat())->create(['numero_manifiesto' => 'AJENO-1']);

    $this->actingAs($user)
        ->get(route('sat.manifiesto.index'))
        ->assertOk()
        ->assertSee('MIO-1')
        ->assertDontSee('AJENO-1');
});
