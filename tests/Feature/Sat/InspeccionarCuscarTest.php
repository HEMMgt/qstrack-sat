<?php

/**
 * Cuscar UTF-16 LE con saltos CRLF, que es como los generan los sistemas de las
 * navieras. Datos ficticios: los archivos reales llevan NIT, guía y nombre de
 * clientes y no deben entrar al repositorio.
 */
function cuscarDePrueba(): string
{
    $segmentos = "UNB+UNOA:2+7400000000000+7409000000000+20260901:0648+0001+6'\r\n"
        ."UNH+26000001+CUSCAR:D:01A:UN'\r\n"
        ."BGM+785+21C26000001+9'\r\n"
        ."RFF+AFB'\r\n"
        ."NAD+AE+7400000000000::9'\r\n"
        ."UNT+5+26000001'\r\n"
        ."UNZ+1+0001'\r\n";

    return "\xFF\xFE".mb_convert_encoding($segmentos, 'UTF-16LE', 'UTF-8');
}

function rutaTemporal(string $contenido): string
{
    $ruta = tempnam(sys_get_temp_dir(), 'cuscar').'.244';
    file_put_contents($ruta, $contenido);

    return $ruta;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/cuscar*.244') as $archivo) {
        @unlink($archivo);
    }
});

it('reporta que transmite lo mismo que el sistema legacy', function () {
    $ruta = rutaTemporal(cuscarDePrueba());

    $this->artisan('sat:inspeccionar-cuscar', ['ruta' => $ruta, '--simular-legacy' => true])
        ->expectsOutputToContain('IDÉNTICOS')
        ->assertSuccessful();
});

it('detecta la diferencia si se dejaran de enviar los retornos de carro', function () {
    // Es el fallo que provocó los rechazos: el cuscar llegaba en una sola línea.
    config()->set('sat.cuscar.newline_mode', 'todos');

    $ruta = rutaTemporal(cuscarDePrueba());

    $this->artisan('sat:inspeccionar-cuscar', ['ruta' => $ruta, '--simular-legacy' => true])
        ->expectsOutputToContain('DIFIEREN')
        ->assertFailed();
});

it('describe la codificación del archivo', function () {
    $ruta = rutaTemporal(cuscarDePrueba());

    $this->artisan('sat:inspeccionar-cuscar', ['ruta' => $ruta])
        ->expectsOutputToContain('UTF-16 LE')
        ->assertSuccessful();
});

it('avisa cuando el archivo no existe', function () {
    $this->artisan('sat:inspeccionar-cuscar', ['ruta' => '/no/existe.244'])
        ->expectsOutputToContain('No se puede leer el archivo')
        ->assertFailed();
});

it('avisa cuando el archivo de comparación no existe', function () {
    $ruta = rutaTemporal(cuscarDePrueba());

    $this->artisan('sat:inspeccionar-cuscar', [
        'ruta' => $ruta,
        '--comparar' => '/no/existe.txt',
    ])->expectsOutputToContain('No se puede leer el archivo de comparación')->assertFailed();
});
