<?php

use App\Rules\CuscarFileName;
use Illuminate\Support\Facades\Validator;

function validaNombre(string $name): bool
{
    return Validator::make(['archivo' => $name], ['archivo' => [new CuscarFileName]])->passes();
}

it('acepta nombres con el formato de la SAT', function (string $name) {
    expect(validaNombre($name))->toBeTrue();
})->with([
    'importación' => 'P0011234.123',
    'exportación' => 'E9995678.001',
    'correlativo con ceros' => 'P0010000.365',
]);

it('rechaza nombres inválidos', function (string $name) {
    expect(validaNombre($name))->toBeFalse();
})->with([
    'tipo de servicio incorrecto' => 'X0011234.123',
    'correlativo no numérico' => 'P001ABCD.123',
    'extensión corta' => 'P0011234.12',
    'extensión no numérica' => 'P0011234.abc',
    'demasiado largo' => 'P0011234.1234',
    'demasiado corto' => 'P001234.12',
    'sin punto' => 'P00112341234',
    'punto fuera de lugar' => 'P001123.4123',
    'recorrido de directorios' => '../P0011234.123',
]);

it('descompone el nombre en sus partes', function () {
    expect(CuscarFileName::parse('E9995678.001'))->toBe([
        'service_type' => 'E',
        'correlativo' => '5678',
        'julian_extension' => '001',
    ]);
});
