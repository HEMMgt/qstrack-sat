<?php

use App\Services\Sat\Support\CuscarContent;

it('elimina los saltos de línea en todas sus variantes', function () {
    // El sistema legacy solo quitaba \n, dejando los \r de un archivo CRLF.
    expect(CuscarContent::prepare("uno\ndos\r\ntres\rcuatro"))
        ->toBe('unodostrescuatro');
});

it('elimina el BOM al inicio del archivo', function () {
    expect(CuscarContent::prepare("\xEF\xBB\xBFUNB+UNOA"))
        ->toBe('UNB+UNOA');
});

it('conserva el contenido cuando strip_newlines está desactivado', function () {
    config()->set('sat.cuscar.strip_newlines', false);

    expect(CuscarContent::prepare("uno\ndos"))->toBe("uno\ndos");
});

it('deja intacto un contenido que ya viene en una sola línea', function () {
    expect(CuscarContent::prepare('UNB+UNOA:1+123'))->toBe('UNB+UNOA:1+123');
});
