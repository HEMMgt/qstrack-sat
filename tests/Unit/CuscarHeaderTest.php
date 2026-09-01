<?php

use App\Services\Sat\Support\CuscarHeader;

function cuscarConCabecera(string $unb, string $bgm = "BGM+785+21C26000477+9'"): string
{
    $segmentos = $unb."\r\n"
        ."UNH+26000477+CUSCAR:D:01A:UN'\r\n"
        .$bgm."\r\n"
        ."RFF+AFB'\r\n"
        ."UNZ+1+0577'\r\n";

    return "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');
}

it('lee los datos de cabecera de un cuscar', function () {
    $header = CuscarHeader::fromContent(cuscarConCabecera(
        "UNB+UNOA:2+7400000000926+7409000030025+20260901:0837+0577+6'",
    ));

    expect($header->emisor)->toBe('7400000000926')
        ->and($header->destinatario)->toBe('7409000030025')
        ->and($header->fechaHora)->toBe('20260901:0837')
        ->and($header->referencia)->toBe('0577')
        ->and($header->numeroManifiesto)->toBe('21C26000477')
        ->and($header->tipoMensaje)->toBe('CUSCAR');
});

it('descarta el calificador del emisor', function () {
    $header = CuscarHeader::fromContent(cuscarConCabecera(
        "UNB+UNOA:2+7400000000926:ZZZ+7409000030025:ZZ+20260901:0837+0577+6'",
    ));

    expect($header->emisor)->toBe('7400000000926')
        ->and($header->destinatario)->toBe('7409000030025');
});

it('devuelve null en lo que falte, sin fallar', function () {
    $header = CuscarHeader::fromContent("UNB+UNOA:2+7400000000926'");

    expect($header->emisor)->toBe('7400000000926')
        ->and($header->destinatario)->toBeNull()
        ->and($header->numeroManifiesto)->toBeNull()
        ->and($header->tipoMensaje)->toBeNull();
});

it('no revienta con un archivo vacío o sin segmentos reconocibles', function () {
    expect(CuscarHeader::fromContent('')->emisor)->toBeNull()
        ->and(CuscarHeader::fromContent('esto no es un cuscar')->emisor)->toBeNull();
});

it('lee la cabecera aunque los segmentos vengan pegados sin saltos', function () {
    $texto = "UNB+UNOA:2+7400000000926+7409000030025+20260901:0837+0577+6'"
        ."UNH+26000477+CUSCAR:D:01A:UN'BGM+785+21C26000477+9'";

    $header = CuscarHeader::fromContent($texto);

    expect($header->emisor)->toBe('7400000000926')
        ->and($header->numeroManifiesto)->toBe('21C26000477');
});

it('compara el emisor contra el código de la credencial', function () {
    $header = CuscarHeader::fromContent(cuscarConCabecera(
        "UNB+UNOA:2+7400000000926+7409000030025+20260901:0837+0577+6'",
    ));

    expect($header->emisorCoincideCon('7400000000926'))->toBeTrue()
        // Sin código capturado no se restringe nada.
        ->and($header->emisorCoincideCon(null))->toBeTrue()
        ->and($header->emisorCoincideCon(''))->toBeTrue()
        ->and($header->emisorCoincideCon('28593111'))->toBeFalse();
});
