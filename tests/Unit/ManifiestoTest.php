<?php

use App\Services\Sat\DTO\Manifiesto;

it('convierte las cadenas vacías de la SAT en null', function () {
    $manifiesto = Manifiesto::fromArray([
        'numeroManifiesto' => '  2026-001  ',
        'estado' => '',
        'firmaElectronica' => '   ',
    ]);

    expect($manifiesto->numeroManifiesto)->toBe('2026-001')
        ->and($manifiesto->estado)->toBeNull()
        ->and($manifiesto->firmaElectronica)->toBeNull();
});

it('no falla cuando la SAT omite campos', function () {
    $manifiesto = Manifiesto::fromArray([]);

    expect($manifiesto->isEmpty())->toBeTrue()
        ->and($manifiesto->nombreMedioTransporte)->toBeNull();
});

it('ignora valores que no son escalares', function () {
    $manifiesto = Manifiesto::fromArray(['estado' => ['inesperado']]);

    expect($manifiesto->estado)->toBeNull();
});

it('expone los doce campos del manifiesto', function () {
    expect(Manifiesto::fromArray([])->toArray())->toHaveCount(12)
        ->and(Manifiesto::labels())->toHaveCount(12);
});
