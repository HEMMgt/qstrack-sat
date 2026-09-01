<?php

use App\Services\Sat\Support\CuscarContent;

it('elimina los avances de línea y conserva los retornos de carro', function () {
    // Reproduce el replace(/\n/g,"") del sistema legacy: la SAT usa los CR para
    // numerar las líneas de sus mensajes de error, y quitarlos dejaba el cuscar
    // en una sola línea.
    expect(CuscarContent::prepare("uno\ndos\r\ntres\rcuatro"))
        ->toBe("unodos\rtres\rcuatro");
});

it('puede quitar todos los saltos si se configura así', function () {
    config()->set('sat.cuscar.newline_mode', 'todos');

    expect(CuscarContent::prepare("uno\ndos\r\ntres\rcuatro"))->toBe('unodostrescuatro');
});

it('puede transmitir el archivo tal cual', function () {
    config()->set('sat.cuscar.newline_mode', 'ninguno');

    expect(CuscarContent::prepare("uno\r\ndos"))->toBe("uno\r\ndos");
});

it('elimina el BOM al inicio del archivo', function () {
    expect(CuscarContent::prepare("\xEF\xBB\xBFUNB+UNOA"))
        ->toBe('UNB+UNOA');
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

    expect($resultado)->toBe("UNB+UNOA:2+740000000926'\rBGM+785+09E26007084+9'\r")
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

it('transmite los mismos bytes que el sistema legacy', function () {
    // El legacy descarga el archivo en el navegador, lo decodifica por su marca
    // de orden de bytes y le aplica replace(/\n/g,""). Esto reproduce esa cadena
    // sobre un cuscar UTF-16 con saltos CRLF, que es el caso real.
    $segmentos = "UNB+UNOA:2+740000000926+20260831:1220+7084+6'\r\n"
        ."UNH+26007084+CUSCAR:D:01A:UN'\r\n"
        ."BGM+785+09E26007084+9'\r\n"
        ."UNZ+1+7084'\r\n";

    $archivo = "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');
    $legacy = str_replace("\n", '', $segmentos);

    expect(CuscarContent::prepare($archivo))->toBe($legacy);
});
