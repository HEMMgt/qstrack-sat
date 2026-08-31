<?php

use App\Models\CuscarFile;
use App\Models\SatCredential;
use App\Models\SatManifest;
use App\Models\SatTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SatFake;

/**
 * Prueba de humo: cada pantalla debe compilar y responder 200. Un error de
 * sintaxis en una plantilla solo aparece al renderizarla de verdad.
 */
beforeEach(function () {
    Storage::fake('cuscar');
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);
});

it('renderiza las pantallas del operador', function (string $ruta) {
    $user = User::factory()->operador()->create();
    SatCredential::factory()->create()->users()->attach($user, ['assigned_at' => now()]);

    $this->actingAs($user)->get(route($ruta))->assertOk();
})->with([
    'dashboard',
    'sat.nit.create',
    'sat.cuscar.validar.create',
    'sat.cuscar.create',
    'sat.cuscar.index',
    'sat.manifiesto.create',
    'sat.manifiesto.index',
    'sat.credencial.edit',
    'sat.transacciones.index',
    'profile.edit',
]);

it('renderiza las pantallas de administración', function (string $ruta) {
    $this->actingAs(User::factory()->admin()->create())->get(route($ruta))->assertOk();
})->with([
    'admin.usuarios.index',
    'admin.usuarios.create',
    'admin.credenciales.index',
    'admin.credenciales.create',
    'admin.bitacora.index',
]);

it('renderiza las pantallas de edición y detalle', function () {
    $admin = User::factory()->admin()->create();
    $credential = SatCredential::factory()->create();
    $credential->users()->attach($admin, ['assigned_at' => now()]);

    $this->actingAs($admin)->get(route('admin.usuarios.edit', $admin))->assertOk();
    $this->actingAs($admin)->get(route('admin.credenciales.edit', $credential))->assertOk();
    $this->actingAs($admin)->get(route('admin.credenciales.show', $credential))->assertOk();

    $manifest = SatManifest::factory()->for($admin)->create();
    $this->actingAs($admin)->get(route('sat.manifiesto.show', $manifest))->assertOk();

    $file = CuscarFile::factory()->for($admin)->for($credential, 'credential')->create();
    // Sin el archivo en disco: la pantalla debe avisar, no reventar.
    $this->actingAs($admin)->get(route('sat.cuscar.show', $file))->assertOk()
        ->assertSee('ya no está disponible');

    Storage::disk('cuscar')->put($file->storage_path, "UNB+UNOA\nUNH+1");
    $this->actingAs($admin)->get(route('sat.cuscar.show', $file))->assertOk()
        ->assertSee('UNB+UNOA');

    $this->actingAs($admin)->get(route('sat.cuscar.validar.show', $file))->assertOk();
});

it('renderiza el detalle de una transacción', function () {
    Http::fake([SatFake::url('validarNit') => SatFake::html(403)]);

    $user = User::factory()->operador()->create();
    SatCredential::factory()->create()->users()->attach($user, ['assigned_at' => now()]);

    $this->actingAs($user)->post(route('sat.nit.store'), ['nit' => '12345678']);

    $this->actingAs($user)
        ->get(route('sat.transacciones.show', SatTransaction::sole()))
        ->assertOk()
        // La respuesta cruda queda a la vista para poder diagnosticar.
        ->assertSee('Acceso denegado')
        ->assertSee('***');
});

it('renderiza las pantallas públicas', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('password.request'))->assertOk();
    $this->get('/')->assertRedirect(route('dashboard'));
});
