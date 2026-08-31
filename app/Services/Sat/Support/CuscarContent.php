<?php

namespace App\Services\Sat\Support;

/**
 * Normaliza el contenido de un archivo cuscar antes de enviarlo a la SAT.
 */
final class CuscarContent
{
    private const BOM = "\xEF\xBB\xBF";

    public static function prepare(string $raw): string
    {
        // El sistema legacy parchaba el BOM en JavaScript recortando dos
        // caracteres, y solo si el navegador era Firefox.
        if (str_starts_with($raw, self::BOM)) {
            $raw = substr($raw, strlen(self::BOM));
        }

        if (config('sat.cuscar.strip_newlines')) {
            // El legacy solo quitaba \n, así que un archivo con CRLF le dejaba
            // los \r sueltos dentro del contenido enviado.
            $raw = str_replace(["\r\n", "\n", "\r"], '', $raw);
        }

        return $raw;
    }
}
