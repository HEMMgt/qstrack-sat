<?php

use App\Enums\CuscarStatus;
use App\Models\CuscarFile;
use App\Models\SatCredential;
use App\Models\SatTransaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SatFake;

function operadorCuscar(): User
{
    $user = User::factory()->operador()->create();
    SatCredential::factory()->create()->users()->attach($user, ['assigned_at' => now()]);

    return $user;
}

beforeEach(function () {
    Storage::fake('cuscar');
    Http::fake([SatFake::url('probar') => Http::response('Servicio web activo')]);
});

it('guarda el archivo en el almacén privado y registra sus datos', function () {
    $user = operadorCuscar();

    $this->actingAs($user)
        ->post(route('sat.cuscar.store'), [
            'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', "UNB+UNOA\nUNH+1"),
        ])
        ->assertRedirect();

    $file = CuscarFile::sole();

    expect($file->filename)->toBe('P0011234.123')
        ->and($file->service_type)->toBe('P')
        ->and($file->correlativo)->toBe('1234')
        ->and($file->julian_extension)->toBe('123')
        ->and($file->status)->toBe(CuscarStatus::Cargado)
        ->and($file->sha256)->toHaveLength(64)
        // Dentro de una carpeta por usuario, con identificador único delante.
        ->and($file->storage_path)->toStartWith($user->id.'/');

    Storage::disk('cuscar')->assertExists($file->storage_path);
});

it('rechaza los nombres que no cumplen el formato de la SAT', function (string $nombre) {
    $this->actingAs(operadorCuscar())
        ->post(route('sat.cuscar.store'), [
            'archivo' => UploadedFile::fake()->createWithContent($nombre, 'UNB'),
        ])
        ->assertSessionHasErrors('archivo');

    expect(CuscarFile::count())->toBe(0);
})->with(['X0011234.123', 'P001ABCD.123', 'P001234.12', 'P0011234.abc']);

it('rechaza un archivo que supera el tamaño máximo', function () {
    config()->set('sat.cuscar.max_bytes', 100);

    $this->actingAs(operadorCuscar())
        ->post(route('sat.cuscar.store'), [
            'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', str_repeat('A', 200)),
        ])
        ->assertSessionHasErrors('archivo');
});

it('exige seleccionar un archivo', function () {
    $this->actingAs(operadorCuscar())
        ->post(route('sat.cuscar.store'), [])
        ->assertSessionHasErrors('archivo');
});

it('transmite el contenido del disco igual que el sistema legacy', function () {
    Http::fake([
        SatFake::url('ingresarCuscar') => Http::response(SatFake::exito(
            ['firmaElectronica' => 'FIRMA-123', 'numeroManifiesto' => 'GT-777'],
            'Archivo recibido',
        )),
    ]);

    $user = operadorCuscar();
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', "UNB+UNOA\r\nUNH+1\nUNT+2"),
    ]);

    $file = CuscarFile::sole();

    $this->actingAs($user)
        ->post(route('sat.cuscar.send', $file))
        ->assertRedirect(route('sat.cuscar.validar.create', ['nombreArchivo' => 'P0011234.123']));

    // La cadena del navegador del legacy transmite los CRLF intactos; el LF
    // suelto desaparece en su replace(/\n/g,"") y no se recupera. La SAT usa
    // esos saltos para numerar las líneas de sus mensajes de error.
    Http::assertSent(function ($request) {
        $contenido = $request['contenidoArchivo'];

        return $request->url() === SatFake::url('ingresarCuscar')
            && $contenido === "UNB+UNOA\r\nUNH+1UNT+2"
            // Ningún CR suelto: siempre como parte de un CRLF.
            && ! preg_match('/\r(?!\n)/', $contenido);
    });

    $file->refresh();

    expect($file->status)->toBe(CuscarStatus::Enviado)
        ->and($file->firma_electronica)->toBe('FIRMA-123')
        ->and($file->numero_manifiesto)->toBe('GT-777')
        ->and($file->sent_at)->not->toBeNull()
        ->and($file->sat_transaction_id)->toBe(SatTransaction::latest('id')->first()->id);
});

it('marca el archivo como rechazado cuando la SAT lo rehúsa', function () {
    Http::fake([
        SatFake::url('ingresarCuscar') => Http::response(SatFake::error('Estructura inválida en la línea 3')),
    ]);

    $user = operadorCuscar();
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', 'UNB'),
    ]);

    $this->actingAs($user)
        ->post(route('sat.cuscar.send', CuscarFile::sole()))
        ->assertSessionHas('sat_error', 'Estructura inválida en la línea 3');

    expect(CuscarFile::sole()->status)->toBe(CuscarStatus::Rechazado);
});

it('exige confirmación explícita para reenviar un cuscar ya transmitido', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    $user = operadorCuscar();
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', 'UNB'),
    ]);
    $file = CuscarFile::sole();

    $this->actingAs($user)->post(route('sat.cuscar.send', $file));
    expect($file->refresh()->status)->toBe(CuscarStatus::Enviado);

    // Sin la casilla, no se vuelve a llamar a la SAT.
    $this->actingAs($user)
        ->post(route('sat.cuscar.send', $file))
        ->assertSessionHas('sat_error');

    expect(SatTransaction::where('endpoint', 'ingresarCuscar')->count())->toBe(1);

    // Con la casilla marcada, sí.
    $this->actingAs($user)->post(route('sat.cuscar.send', $file), ['reenviar' => '1']);

    expect(SatTransaction::where('endpoint', 'ingresarCuscar')->count())->toBe(2);
});

it('impide enviar el archivo de otro usuario', function () {
    $file = CuscarFile::factory()->for(operadorCuscar())->create();

    $this->actingAs(operadorCuscar())
        ->post(route('sat.cuscar.send', $file))
        ->assertForbidden();
});

it('impide descargar el archivo de otro usuario', function () {
    $file = CuscarFile::factory()->for(operadorCuscar())->create();

    $this->actingAs(operadorCuscar())
        ->get(route('sat.cuscar.download', $file))
        ->assertForbidden();
});

it('no guarda los archivos en una ruta accesible por el navegador', function () {
    $user = operadorCuscar();
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', 'UNB'),
    ]);

    // El sistema legacy los dejaba en uploads/cuscar/, servido públicamente.
    expect(config('filesystems.disks.cuscar.root'))->not->toContain('/public')
        ->and(config('filesystems.disks.cuscar.visibility'))->toBe('private');
});

it('transmite en texto plano un cuscar que viene en UTF-16', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    // Los sistemas de las navieras generan los cuscar en UTF-16 con marca de
    // orden de bytes. Enviados tal cual, la SAT no puede leer el segmento BGM.
    $segmentos = "UNB+UNOA:2+740000000926'\r\nBGM+785+09E26007084+9'\r\nUNZ+1+7084'";
    $utf16 = "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');

    $user = operadorCuscar();
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', $utf16),
    ]);

    $this->actingAs($user)->post(route('sat.cuscar.send', CuscarFile::sole()));

    Http::assertSent(function ($request) {
        $contenido = $request['contenidoArchivo'];

        return str_starts_with($contenido, 'UNB+UNOA')
            && str_contains($contenido, "BGM+785+09E26007084+9'")
            && ! str_contains($contenido, "\x00")
            && ! str_contains($contenido, "\xFF\xFE");
    });
});

it('muestra la vista previa legible aunque el archivo sea UTF-16', function () {
    $utf16 = "\xFF\xFE".mb_convert_encoding("UNB+UNOA:2'\r\nBGM+785+1+9'", 'UTF-16LE', 'UTF-8');

    $user = operadorCuscar();
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', $utf16),
    ]);

    $this->actingAs($user)
        ->get(route('sat.cuscar.show', CuscarFile::sole()))
        ->assertOk()
        ->assertSee('BGM+785+1+9');
});

/**
 * Cuscar de Correo Directo: el emisor va en el tercer elemento del UNB.
 */
function cuscarDeEmisor(string $emisor): string
{
    $segmentos = "UNB+UNOA:2+{$emisor}+7409000030025+20260901:0837+0577+6'\r\n"
        ."UNH+26000477+CUSCAR:D:01A:UN'\r\n"
        ."BGM+785+21C26000477+9'\r\n"
        ."UNZ+1+0577'\r\n";

    return "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');
}

function operadorConGln(?string $gln): App\Models\User
{
    $user = User::factory()->operador()->create();
    SatCredential::factory()->create(['name' => 'Correo Directo, S.A.', 'gln' => $gln])
        ->users()->attach($user, ['assigned_at' => now()]);

    return $user;
}

it('guarda el emisor y el manifiesto declarados en el archivo', function () {
    $this->actingAs(operadorConGln(null))->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('7400000000926'),
        ),
    ]);

    $file = CuscarFile::sole();

    expect($file->emisor)->toBe('7400000000926')
        ->and($file->numero_manifiesto_declarado)->toBe('21C26000477');
});

it('transmite cuando el emisor corresponde a la credencial', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    $user = operadorConGln('7400000000926');
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('7400000000926'),
        ),
    ]);

    $this->actingAs($user)->post(route('sat.cuscar.send', CuscarFile::sole()))
        ->assertSessionMissing('sat_error');

    expect(CuscarFile::sole()->status)->toBe(CuscarStatus::Enviado);
});

it('no transmite un archivo de otro emisor', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    // Es lo que ocurrió en producción: manifiestos de Correo Directo enviados
    // con la credencial de otra empresa. La SAT los rechazaba en el segmento UNB
    // con un mensaje que no explicaba la causa.
    $user = operadorConGln('28593111');
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('7400000000926'),
        ),
    ]);

    $this->actingAs($user)
        ->post(route('sat.cuscar.send', CuscarFile::sole()))
        ->assertSessionHas('sat_error');

    // Ni siquiera se intenta: no se gasta un envío para que la SAT lo rechace.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ingresarCuscar'));

    expect(CuscarFile::sole()->status)->toBe(CuscarStatus::Cargado);
});

it('el mensaje nombra el emisor del archivo y la credencial asignada', function () {
    $user = operadorConGln('28593111');
    $credencial = $user->satCredential();

    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('7400000000926'),
        ),
    ]);

    $this->actingAs($user)->post(route('sat.cuscar.send', CuscarFile::sole()));

    expect(session('sat_error'))
        ->toContain('7400000000926')
        ->toContain($credencial->label());
});

it('no restringe nada mientras la credencial no tenga código de emisor', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    $user = operadorConGln(null);
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('7400000000926'),
        ),
    ]);

    $this->actingAs($user)->post(route('sat.cuscar.send', CuscarFile::sole()));

    expect(CuscarFile::sole()->status)->toBe(CuscarStatus::Enviado);
});

it('avisa en la pantalla de revisión antes de transmitir', function () {
    $user = operadorConGln('28593111');
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('7400000000926'),
        ),
    ]);

    $this->actingAs($user)
        ->get(route('sat.cuscar.show', CuscarFile::sole()))
        ->assertOk()
        ->assertSee('El emisor del archivo no corresponde a su credencial')
        ->assertSee('7400000000926');
});

it('detecta la credencial equivocada aunque la asignada no tenga código de emisor', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    // El escenario exacto que se dio en producción: al operador se le asignó la
    // credencial de otra empresa, que además no tenía código capturado. Como sí
    // existe registrada la credencial del emisor del archivo, el sistema sabe
    // que la asignada no es la que corresponde.
    SatCredential::factory()->create(['name' => 'Correo Directo, S.A.', 'gln' => '7400000000926']);

    $user = operadorConGln(null);
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('7400000000926'),
        ),
    ]);

    $this->actingAs($user)->post(route('sat.cuscar.send', CuscarFile::sole()));

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ingresarCuscar'));

    expect(session('sat_error'))->toContain('corresponde a Correo Directo, S.A.')
        ->and(CuscarFile::sole()->status)->toBe(CuscarStatus::Cargado);
});

it('transmite cuando ninguna credencial reclama ese emisor', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    $user = operadorConGln(null);
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent(
            'P0011234.123', cuscarDeEmisor('9999999999999'),
        ),
    ]);

    $this->actingAs($user)->post(route('sat.cuscar.send', CuscarFile::sole()));

    expect(CuscarFile::sole()->status)->toBe(CuscarStatus::Enviado);
});

it('transmite los acentos transliterados y la vista previa lo refleja', function () {
    Http::fake([SatFake::url('ingresarCuscar') => Http::response(SatFake::exito())]);

    $segmentos = "UNB+UNOA:2+7400000000926+7409000030025+20260901:0852+0001+6'\r\n"
        ."FTX+AAA+++MATERIALES DIDÁCTICOS'\r\n"
        ."UNZ+1+0001'\r\n";
    $archivo = "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');

    $user = operadorConGln('7400000000926');
    $this->actingAs($user)->post(route('sat.cuscar.store'), [
        'archivo' => UploadedFile::fake()->createWithContent('P0011234.123', $archivo),
    ]);

    // La pantalla de revisión muestra lo que de verdad se transmitirá.
    $this->actingAs($user)
        ->get(route('sat.cuscar.show', CuscarFile::sole()))
        ->assertSee('MATERIALES DIDACTICOS')
        ->assertDontSee('DIDÁCTICOS');

    $this->actingAs($user)->post(route('sat.cuscar.send', CuscarFile::sole()));

    Http::assertSent(fn ($request) => $request->url() === SatFake::url('ingresarCuscar')
        && str_contains($request['contenidoArchivo'], "MATERIALES DIDACTICOS'")
        && ! str_contains($request['contenidoArchivo'], 'Á'));
});
