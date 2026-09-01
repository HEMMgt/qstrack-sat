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

it('convierte un cuscar UTF-16 LE a texto plano', function () {
    // Es como vienen los archivos que generan los sistemas de las navieras.
    // Sin convertirlos, la SAT responde que no puede obtener el segmento BGM.
    $segmentos = "UNB+UNOA:2+740000000926'\r\nBGM+785+09E26007084+9'\r\n";
    $utf16 = "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');

    $resultado = CuscarContent::prepare($utf16);

    expect($resultado)->toBe("UNB+UNOA:2+740000000926'BGM+785+09E26007084+9'")
        ->and($resultado)->not->toContain("\x00");
});

it('convierte un cuscar UTF-16 BE a texto plano', function () {
    $utf16 = "\xFE\xFF".mb_convert_encoding("BGM+785+1+9'", 'UTF-16BE', 'UTF-8');

    expect(CuscarContent::prepare($utf16))->toBe("BGM+785+1+9'");
});

it('detecta UTF-16 aunque falte la marca de orden de bytes', function () {
    $sinBom = mb_convert_encoding("BGM+785+1+9'", 'UTF-16LE', 'UTF-8');

    expect(CuscarContent::prepare($sinBom))
        ->toBe("BGM+785+1+9'")
        ->and(CuscarContent::prepare($sinBom))->not->toContain("\x00");
});

it('nunca deja pasar bytes nulos a la SAT', function () {
    // Un archivo con longitud impar o codificación rara no debe romper el envío.
    $roto = "\xFF\xFEB\x00G\x00M\x00+\x001\x00'\x00\x41";

    expect(CuscarContent::prepare($roto))->not->toContain("\x00");
});

it('deja intacto un archivo que ya viene en texto plano', function () {
    expect(CuscarContent::prepare("UNB+UNOA:2'\nBGM+785+1+9'"))
        ->toBe("UNB+UNOA:2'BGM+785+1+9'");
});
