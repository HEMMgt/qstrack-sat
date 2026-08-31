<?php

use App\Models\SatCredential;
use App\Models\SatTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\Support\SatFake;

function operadorConCredencial(): User
{
    $user = User::factory()->operador()->create();
    $credential = SatCredential::factory()->create(['nit' => '28593111', 'password' => 'clave-secreta']);
    $credential->users()->attach($user, ['assigned_at' => now()]);

    return $user;
}

beforeEach(function () {
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);
});

it('muestra el formulario al operador con credencial', function () {
    $this->actingAs(operadorConCredencial())
        ->get(route('sat.nit.create'))
        ->assertOk()
        ->assertSee('Validar NIT');
});

it('nunca expone la contraseña de la SAT en el HTML', function () {
    $response = $this->actingAs(operadorConCredencial())->get(route('sat.nit.create'));

    // El sistema legacy la imprimía en un <input type="hidden">.
    $response->assertDontSee('clave-secreta');
});

it('consulta el NIT y muestra la descripción de la SAT', function () {
    Http::fake([
        SatFake::url('validarNit') => Http::response(SatFake::exito(descripcion: 'NIT válido y activo')),
    ]);

    $this->actingAs(operadorConCredencial())
        ->post(route('sat.nit.store'), ['nit' => '12345678'])
        ->assertRedirect()
        ->assertSessionHas('sat_result.exito', true)
        ->assertSessionHas('sat_result.descripcion', 'NIT válido y activo');

    expect(SatTransaction::sole()->succeeded)->toBeTrue();
});

it('muestra el rechazo de la SAT sin tratarlo como error del sistema', function () {
    Http::fake([SatFake::url('validarNit') => Http::response(SatFake::error('El NIT no existe'))]);

    $this->actingAs(operadorConCredencial())
        ->post(route('sat.nit.store'), ['nit' => '00000000'])
        ->assertRedirect()
        ->assertSessionHas('sat_result.exito', false)
        ->assertSessionHas('sat_result.descripcion', 'El NIT no existe');
});

it('informa al usuario cuando la SAT no está disponible', function () {
    Http::fake([SatFake::url('validarNit') => SatFake::html(403)]);

    $this->actingAs(operadorConCredencial())
        ->post(route('sat.nit.store'), ['nit' => '12345678'])
        ->assertRedirect()
        ->assertSessionHas('sat_error');

    // Aun fallando, la llamada quedó registrada.
    expect(SatTransaction::sole()->error_class)->toBe('SatUnavailableException');
});

it('rechaza un NIT con caracteres no permitidos', function () {
    $this->actingAs(operadorConCredencial())
        ->post(route('sat.nit.store'), ['nit' => "12345'; DROP TABLE users--"])
        ->assertSessionHasErrors('nit');

    expect(SatTransaction::count())->toBe(0);
});

it('exige NIT', function () {
    $this->actingAs(operadorConCredencial())
        ->post(route('sat.nit.store'), ['nit' => ''])
        ->assertSessionHasErrors('nit');
});

it('redirige al invitado al login', function () {
    $this->get(route('sat.nit.create'))->assertRedirect(route('login'));
});

it('niega el acceso al auditor', function () {
    $user = User::factory()->auditor()->create();
    SatCredential::factory()->create()->users()->attach($user, ['assigned_at' => now()]);

    $this->actingAs($user)->get(route('sat.nit.create'))->assertForbidden();
    $this->actingAs($user)->post(route('sat.nit.store'), ['nit' => '123'])->assertForbidden();
});

it('responde 403 a quien no tiene permiso, aunque tampoco tenga credencial', function () {
    // El permiso se comprueba antes que la credencial: a quien no puede usar la
    // pantalla no se le pide una credencial que no le serviría.
    $this->actingAs(User::factory()->auditor()->create())
        ->get(route('sat.nit.create'))
        ->assertForbidden();
});

it('envía al usuario sin credencial a la pantalla de credencial', function () {
    $this->actingAs(User::factory()->operador()->create())
        ->get(route('sat.nit.create'))
        ->assertRedirect(route('sat.credencial.edit'))
        ->assertSessionHas('sat_error');
});

it('bloquea al usuario cuya credencial fue desactivada', function () {
    $user = User::factory()->operador()->create();
    SatCredential::factory()->inactive()->create()->users()->attach($user, ['assigned_at' => now()]);

    $this->actingAs($user)->get(route('sat.nit.create'))->assertRedirect(route('sat.credencial.edit'));
});

it('expulsa al usuario desactivado', function () {
    $user = operadorConCredencial();
    $user->update(['is_active' => false]);

    $this->actingAs($user)->get(route('sat.nit.create'))->assertRedirect(route('login'));
    expect(auth()->check())->toBeFalse();
});
