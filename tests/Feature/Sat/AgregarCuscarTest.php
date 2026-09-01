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

it('transmite el contenido del disco sin saltos de línea', function () {
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

    Http::assertSent(fn ($request) => $request->url() === SatFake::url('ingresarCuscar')
        && $request['contenidoArchivo'] === 'UNB+UNOAUNH+1UNT+2'
        && ! str_contains($request['contenidoArchivo'], "\n")
        && ! str_contains($request['contenidoArchivo'], "\r"));

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
