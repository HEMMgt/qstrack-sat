<?php

use App\Models\SatCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\SatFake;

beforeEach(function () {
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);

    // La rotación exige confirmar la propia contraseña (middleware de Breeze).
    $this->session(['auth.password_confirmed_at' => time()]);
});

function usuarioCon(SatCredential $credential): User
{
    $user = User::factory()->operador()->create();
    $credential->users()->attach($user, ['assigned_at' => now()]);

    return $user;
}

it('actualiza la credencial propia y la guarda cifrada', function () {
    $credential = SatCredential::factory()->create(['nit' => '111', 'password' => 'vieja']);
    $user = usuarioCon($credential);

    $this->actingAs($user)
        ->patch(route('sat.credencial.update'), [
            'nit' => '28593111',
            'password' => 'NuevaClave2026',
            'password_confirmation' => 'NuevaClave2026',
        ])
        ->assertRedirect(route('sat.credencial.edit'))
        ->assertSessionHas('status');

    $credential->refresh();

    expect($credential->nit)->toBe('28593111')
        ->and($credential->password)->toBe('NuevaClave2026')
        ->and($credential->secret_rotated_at)->not->toBeNull();

    // En la base no está el texto plano: el sistema legacy lo guardaba tal cual.
    $stored = DB::table('sat_credentials')->where('id', $credential->id)->value('password');
    expect($stored)->not->toBe('NuevaClave2026')
        ->and($stored)->toStartWith('eyJpdiI6');
});

it('ignora cualquier identificador enviado en el formulario', function () {
    $ajena = SatCredential::factory()->create(['nit' => '999', 'password' => 'clave-ajena']);
    $propia = SatCredential::factory()->create(['nit' => '111', 'password' => 'clave-propia']);
    $user = usuarioCon($propia);

    // Esto es exactamente el ataque que permitía sat/grabar_clave.php, que hacía
    // UPDATE claves_sat WHERE id = $_POST['id'] sin comprobar de quién era.
    $this->actingAs($user)->patch(route('sat.credencial.update'), [
        'id' => $ajena->id,
        'sat_credential_id' => $ajena->id,
        'nit' => '22222222',
        'password' => 'Secuestrada2026',
        'password_confirmation' => 'Secuestrada2026',
    ])->assertRedirect();

    expect($ajena->refresh()->nit)->toBe('999')
        ->and($ajena->password)->toBe('clave-ajena')
        ->and($propia->refresh()->nit)->toBe('22222222');
});

it('no permite rotar la clave sin tener credencial asignada', function () {
    $this->actingAs(User::factory()->operador()->create())
        ->patch(route('sat.credencial.update'), [
            'nit' => '123',
            'password' => 'Clave2026',
            'password_confirmation' => 'Clave2026',
        ])
        ->assertForbidden();
});

it('exige confirmar la contraseña propia antes de rotar', function () {
    $this->session(['auth.password_confirmed_at' => 0]);
    $user = usuarioCon(SatCredential::factory()->create());

    $this->actingAs($user)
        ->patch(route('sat.credencial.update'), [
            'nit' => '123',
            'password' => 'Clave2026',
            'password_confirmation' => 'Clave2026',
        ])
        ->assertRedirect(route('password.confirm'));
});

it('exige que la confirmación de la contraseña coincida', function () {
    $user = usuarioCon(SatCredential::factory()->create(['password' => 'original']));

    $this->actingAs($user)
        ->patch(route('sat.credencial.update'), [
            'nit' => '123',
            'password' => 'Clave2026',
            'password_confirmation' => 'OtraCosa',
        ])
        ->assertSessionHasErrors('password');
});

it('muestra la pantalla sin revelar la contraseña', function () {
    $user = usuarioCon(SatCredential::factory()->create(['password' => 'clave-secreta']));

    $this->actingAs($user)
        ->get(route('sat.credencial.edit'))
        ->assertOk()
        ->assertDontSee('clave-secreta');
});

it('avisa al usuario sin credencial en lugar de fallar', function () {
    $this->actingAs(User::factory()->operador()->create())
        ->get(route('sat.credencial.edit'))
        ->assertOk()
        ->assertSee('No tiene una credencial SAT asignada');
});

it('permite probar la credencial contra la SAT', function () {
    Http::fake([SatFake::url('validarNit') => Http::response(SatFake::exito(descripcion: 'NIT activo'))]);

    $user = usuarioCon(SatCredential::factory()->create(['nit' => '28593111']));

    $this->actingAs($user)
        ->post(route('sat.credencial.probar'))
        ->assertRedirect()
        ->assertSessionHas('sat_result.exito', true);

    Http::assertSent(fn ($request) => $request['nit'] === '28593111');
});
