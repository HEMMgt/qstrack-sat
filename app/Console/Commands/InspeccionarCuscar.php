<?php

namespace App\Console\Commands;

use App\Services\Sat\Support\CuscarContent;
use Illuminate\Console\Command;

/**
 * Herramienta de diagnóstico para cuando la SAT rechaza un cuscar.
 *
 * Muestra cómo está codificado el archivo y qué bytes exactos se le
 * transmitirían, y puede compararlos contra una captura de lo que envía otro
 * sistema para localizar la primera diferencia.
 */
class InspeccionarCuscar extends Command
{
    protected $signature = 'sat:inspeccionar-cuscar
                            {ruta : Archivo cuscar a inspeccionar}
                            {--comparar= : Archivo con el contenido que envía otro sistema}
                            {--guardar= : Escribe en esta ruta el contenido que se transmitiría}';

    protected $description = 'Inspecciona un archivo cuscar y muestra qué se transmitiría a la SAT';

    public function handle(): int
    {
        $ruta = $this->argument('ruta');

        if (! is_readable($ruta)) {
            $this->error("No se puede leer el archivo: {$ruta}");

            return self::FAILURE;
        }

        $crudo = file_get_contents($ruta);
        $transmitido = CuscarContent::prepare($crudo);

        $this->archivoOriginal($ruta, $crudo);
        $this->contenidoTransmitido($transmitido);

        if ($guardar = $this->option('guardar')) {
            file_put_contents($guardar, $transmitido);
            $this->line("Contenido transmitido escrito en <comment>{$guardar}</comment>");
        }

        if ($comparar = $this->option('comparar')) {
            return $this->comparar($transmitido, $comparar);
        }

        return self::SUCCESS;
    }

    private function archivoOriginal(string $ruta, string $crudo): void
    {
        $this->newLine();
        $this->info('ARCHIVO ORIGINAL');
        $this->table(['Dato', 'Valor'], [
            ['Ruta', $ruta],
            ['Tamaño', number_format(strlen($crudo)).' bytes'],
            ['Marca de orden de bytes', $this->describirBom($crudo)],
            ['Codificación deducida', $this->describirCodificacion($crudo)],
            ['Saltos CRLF', substr_count($crudo, "\r\n")],
            ['Saltos LF', substr_count($crudo, "\n")],
            ['Saltos CR', substr_count($crudo, "\r")],
            ['Bytes nulos', substr_count($crudo, "\x00")],
            ['sha256', hash('sha256', $crudo)],
        ]);

        $this->line('  Primeros 48 bytes: <comment>'.$this->hex(substr($crudo, 0, 48)).'</comment>');
        $this->line('  Últimos 32 bytes:  <comment>'.$this->hex(substr($crudo, -32)).'</comment>');
    }

    private function contenidoTransmitido(string $transmitido): void
    {
        $this->newLine();
        $this->info('LO QUE SE TRANSMITE A LA SAT');
        $this->table(['Dato', 'Valor'], [
            ['Tamaño', number_format(strlen($transmitido)).' bytes'],
            ['Saltos CRLF', substr_count($transmitido, "\r\n")],
            ['Saltos LF', substr_count($transmitido, "\n")],
            ['Saltos CR', substr_count($transmitido, "\r")],
            ['Bytes nulos', substr_count($transmitido, "\x00")],
            ['Segmentos EDIFACT', substr_count($transmitido, "'")],
            ['sha256', hash('sha256', $transmitido)],
        ]);

        $this->line('  Inicio: <comment>'.$this->recortar($transmitido, 90).'</comment>');
        $this->line('  Final:  <comment>'.$this->recortar(substr($transmitido, -60), 90).'</comment>');
    }

    private function comparar(string $transmitido, string $rutaComparar): int
    {
        if (! is_readable($rutaComparar)) {
            $this->error("No se puede leer el archivo de comparación: {$rutaComparar}");

            return self::FAILURE;
        }

        $otro = file_get_contents($rutaComparar);

        $this->newLine();
        $this->info('COMPARACIÓN');
        $this->table(['', 'Este sistema', 'Capturado'], [
            ['Tamaño', number_format(strlen($transmitido)), number_format(strlen($otro))],
            ['Saltos CR', substr_count($transmitido, "\r"), substr_count($otro, "\r")],
            ['Saltos LF', substr_count($transmitido, "\n"), substr_count($otro, "\n")],
            ['Segmentos', substr_count($transmitido, "'"), substr_count($otro, "'")],
            ['sha256', substr(hash('sha256', $transmitido), 0, 16).'…', substr(hash('sha256', $otro), 0, 16).'…'],
        ]);

        if ($transmitido === $otro) {
            $this->newLine();
            $this->info('  IDÉNTICOS: este sistema transmite exactamente los mismos bytes.');

            return self::SUCCESS;
        }

        $offset = $this->primeraDiferencia($transmitido, $otro);

        $this->newLine();
        $this->error("  DIFIEREN a partir del byte {$offset}");
        $this->newLine();
        $this->line('  Este sistema: '.$this->contexto($transmitido, $offset));
        $this->line('  Capturado:    '.$this->contexto($otro, $offset));
        $this->newLine();
        $this->line('  Este sistema (hex): <comment>'.$this->hex(substr($transmitido, $offset, 16)).'</comment>');
        $this->line('  Capturado (hex):    <comment>'.$this->hex(substr($otro, $offset, 16)).'</comment>');

        $this->diferenciasDeCaracteres($transmitido, $otro);

        return self::FAILURE;
    }

    /**
     * Resume qué caracteres sobran o faltan, que suele explicar la diferencia
     * mejor que el primer offset divergente.
     */
    private function diferenciasDeCaracteres(string $a, string $b): void
    {
        $filas = [];

        foreach (["\r" => 'CR', "\n" => 'LF', "\x00" => 'NUL', '&' => 'ampersand', "'" => 'apóstrofo'] as $char => $nombre) {
            $enA = substr_count($a, $char);
            $enB = substr_count($b, $char);

            if ($enA !== $enB) {
                $filas[] = [$nombre, $enA, $enB, $enA > $enB ? 'sobran '.($enA - $enB) : 'faltan '.($enB - $enA)];
            }
        }

        if ($filas !== []) {
            $this->newLine();
            $this->table(['Carácter', 'Este sistema', 'Capturado', 'Diferencia'], $filas);
        }
    }

    private function primeraDiferencia(string $a, string $b): int
    {
        $limite = min(strlen($a), strlen($b));

        for ($i = 0; $i < $limite; $i++) {
            if ($a[$i] !== $b[$i]) {
                return $i;
            }
        }

        return $limite;
    }

    private function contexto(string $texto, int $offset, int $margen = 30): string
    {
        $desde = max(0, $offset - $margen);
        $trozo = substr($texto, $desde, $margen * 2);

        return '…'.$this->visible($trozo).'…';
    }

    private function visible(string $texto): string
    {
        return str_replace(["\r", "\n", "\x00"], ['⟨CR⟩', '⟨LF⟩', '⟨NUL⟩'], $texto);
    }

    private function recortar(string $texto, int $largo): string
    {
        $visible = $this->visible($texto);

        return strlen($visible) > $largo ? substr($visible, 0, $largo).'…' : $visible;
    }

    private function hex(string $bytes): string
    {
        return trim(chunk_split(strtoupper(bin2hex($bytes)), 2, ' '));
    }

    private function describirBom(string $crudo): string
    {
        return match (true) {
            str_starts_with($crudo, "\xFF\xFE") => 'UTF-16 LE (FF FE)',
            str_starts_with($crudo, "\xFE\xFF") => 'UTF-16 BE (FE FF)',
            str_starts_with($crudo, "\xEF\xBB\xBF") => 'UTF-8 (EF BB BF)',
            default => 'sin marca',
        };
    }

    private function describirCodificacion(string $crudo): string
    {
        if (str_starts_with($crudo, "\xFF\xFE") || str_starts_with($crudo, "\xFE\xFF")) {
            return 'UTF-16';
        }

        if (str_contains($crudo, "\x00")) {
            return 'UTF-16 sin marca (tiene bytes nulos)';
        }

        return mb_check_encoding($crudo, 'ASCII') ? 'ASCII' : 'UTF-8 u otra de un byte';
    }
}
