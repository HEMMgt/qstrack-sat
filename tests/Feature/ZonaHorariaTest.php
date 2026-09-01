<?php

it('opera en la zona horaria de Guatemala', function () {
    // La SAT devuelve las fechas en hora local de Guatemala. Con la aplicación
    // en UTC, los registros propios quedaban seis horas adelantados frente a lo
    // que reporta la SAT para la misma operación.
    expect(config('app.timezone'))->toBe('America/Guatemala')
        ->and(now()->getTimezone()->getName())->toBe('America/Guatemala');
});
