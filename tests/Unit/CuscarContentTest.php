<?php

use App\Services\Sat\Support\CuscarContent;

it('reproduce la cadena del navegador: los CRLF sobreviven y los LF sueltos no', function () {
    // El legacy quita los LF (replace(/\n/g,"")), el parser HTML del textarea
    // convierte los CR sueltos en LF, y serializeArray() los devuelve como CRLF.
    // Neto: CRLF intacto, LF suelto eliminado, CR suelto promovido a CRLF.
    expect(CuscarContent::prepare("uno\ndos\r\ntres\rcuatro"))
        ->toBe("unodos\r\ntres\r\ncuatro");
});

it('transmite un archivo CRLF byte a byte igual que lo decodifica', function () {
    // La cadena completa del navegador se anula a sí misma para archivos CRLF:
    // lo que la SAT recibe del legacy es el archivo decodificado sin cambios.
    $segmentos = "UNB+UNOA:2+740000000926'\r\nBGM+785+1+9'\r\nUNZ+1+1'\r\n";
    $archivo = "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');

    expect(CuscarContent::prepare($archivo))->toBe($segmentos);
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

    expect($resultado)->toBe("UNB+UNOA:2+740000000926'\r\nBGM+785+09E26007084+9'\r\n")
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
    // La cadena real del navegador: replace(/\n/g,"") deja CR sueltos, el
    // parser HTML del textarea los convierte en LF y serializeArray() los
    // devuelve como CRLF. Se recorre paso a paso sobre un cuscar UTF-16.
    $segmentos = "UNB+UNOA:2+740000000926+20260831:1220+7084+6'\r\n"
        ."UNH+26007084+CUSCAR:D:01A:UN'\r\n"
        ."BGM+785+09E26007084+9'\r\n"
        ."UNZ+1+7084'\r\n";

    $archivo = "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');

    $paso1 = str_replace("\n", '', $segmentos);   // replace(/\n/g,"")
    $paso2 = str_replace("\r", "\n", $paso1);     // parser HTML: CR -> LF
    $legacy = str_replace("\n", "\r\n", $paso2);  // serializeArray: LF -> CRLF

    expect(CuscarContent::prepare($archivo))->toBe($legacy)
        ->and($legacy)->toBe($segmentos);
});
