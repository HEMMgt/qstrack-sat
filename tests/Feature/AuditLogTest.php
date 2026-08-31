<?php

use App\Models\AuditLog;
use App\Models\CuscarFile;
use App\Models\SatCredential;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SatFake;

function operadorAuditado(): User
{
    $user = User::factory()->operador()->create();
    SatCredential::factory()->create()->users()->attach($user, ['assigned_at' => now()]);

    return $user;
}

beforeEach(function () {
    Storage::fake('cuscar');
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);
});

it('registra el ingreso al sistema con la dirección de origen', function () {
    $user = User::factory()->create(['email' => 'alguien@ejemplo.test']);

    $this->post(route('login'), ['email' => 'alguien@ejemplo.test', 'password' => 'password']);

    $log = AuditLog::where('action', 'auth.login')->sole();

    expect($log->user_id)->toBe($user->id)
        ->and($log->ip_address)->not->toBeNull();
});

it('registra los intentos de ingreso fallidos', function () {
    User::factory()->create(['email' => 'alguien@ejemplo.test']);

    $this->post(route('login'), ['email' => 'alguien@ejemplo.test', 'password' => 'incorrecta']);

    expect(AuditLog::where('action', 'auth.failed')->exists())->toBeTrue();
});

it('registra el cambio de credencial SAT sin guardar la contraseña', function () {
    $credential = SatCredential::factory()->create(['nit' => '111']);
    $user = User::factory()->operador()->create();
    $credential->users()->attach($user, ['assigned_at' => now()]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('sat.credencial.update'), [
            'nit' => '28593111',
            'password' => 'SuperSecreta2026',
            'password_confirmation' => 'SuperSecreta2026',
        ]);

    $log = AuditLog::where('action', 'credencial.secreto_rotado')->sole();

    expect($log->properties['nit']['old'])->toBe('111')
        ->and($log->properties['nit']['new'])->toBe('28593111')
        ->and(json_encode($log->properties))->not->toContain('SuperSecreta2026');
});

it('registra la carga y la transmisión de un cuscar', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    $user = operadorAuditado();

    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', 'UNB'),
    ]);

    $file = CuscarFile::sole();
    $this->actingAs($user)->post(route('sat.cuscar.send', $file));

    expect(AuditLog::where('action', 'sat.cuscar.cargado')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'sat.cuscar.enviado')->exists())->toBeTrue();

    $log = AuditLog::where('action', 'sat.cuscar.enviado')->sole();

    expect($log->auditable_id)->toBe($file->id)
        ->and($log->auditable_type)->toBe(CuscarFile::class);
});

it('distingue un reenvío de un envío nuevo', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    $user = operadorAuditado();
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', 'UNB'),
    ]);
    $file = CuscarFile::sole();

    $this->actingAs($user)->post(route('sat.cuscar.send', $file));
    $this->actingAs($user)->post(route('sat.cuscar.send', $file), ['reenviar' => '1']);

    expect(AuditLog::where('action', 'sat.cuscar.reenviado')->count())->toBe(1);
});

it('registra la administración de usuarios y credenciales', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.usuarios.store'), [
        'name' => 'Nuevo',
        'email' => 'nuevo@ejemplo.test',
        'password' => 'ClaveSegura2026',
        'password_confirmation' => 'ClaveSegura2026',
        'role' => 'operador',
        'is_active' => '1',
    ]);

    $this->actingAs($admin)->post(route('admin.credenciales.store'), [
        'name' => 'Empresa X',
        'nit' => '12345678',
        'password' => 'ClaveSat2026',
        'environment' => 'pruebas',
        'is_active' => '1',
    ]);

    expect(AuditLog::where('action', 'usuario.creado')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'credencial.creada')->exists())->toBeTrue();

    // Ni la contraseña del usuario ni la de la SAT llegan a la bitácora.
    $todo = AuditLog::pluck('properties')->toJson();
    expect($todo)->not->toContain('ClaveSegura2026')->not->toContain('ClaveSat2026');
});

it('sustituye cualquier valor sensible que se le pase por error', function () {
    $log = App\Support\AuditLogger::log('prueba', null, 'con secretos', [
        'password' => 'visible',
        'anidado' => ['api_token' => 'abc', 'clave_sat' => 'xyz', 'nit' => '123'],
    ]);

    expect($log->properties['password'])->toBe('***')
        ->and($log->properties['anidado']['api_token'])->toBe('***')
        ->and($log->properties['anidado']['clave_sat'])->toBe('***')
        ->and($log->properties['anidado']['nit'])->toBe('123');
});

it('solo permite ver la bitácora a quien tiene el permiso', function () {
    $this->actingAs(User::factory()->operador()->create())
        ->get(route('admin.bitacora.index'))->assertForbidden();

    $this->actingAs(User::factory()->auditor()->create())
        ->get(route('admin.bitacora.index'))->assertOk();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.bitacora.index'))->assertOk();
});

it('muestra al operador solo sus propias transacciones', function () {
    Http::fake([SatFake::url('validarNit') => Http::response(SatFake::exito())]);

    $propio = operadorAuditado();
    $this->actingAs($propio)->post(route('sat.nit.store'), ['nit' => '11111111']);

    $otro = operadorAuditado();
    $this->actingAs($otro)->post(route('sat.nit.store'), ['nit' => '22222222']);

    $this->actingAs($propio)
        ->get(route('sat.transacciones.index'))
        ->assertOk()
        ->assertSee($propio->name)
        ->assertDontSee($otro->name);
});

it('permite al auditor ver todas las transacciones', function () {
    Http::fake([SatFake::url('validarNit') => Http::response(SatFake::exito())]);

    $operador = operadorAuditado();
    $this->actingAs($operador)->post(route('sat.nit.store'), ['nit' => '11111111']);

    $this->actingAs(User::factory()->auditor()->create())
        ->get(route('sat.transacciones.index'))
        ->assertOk()
        ->assertSee($operador->name);
});
