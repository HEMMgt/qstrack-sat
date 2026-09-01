<?php

namespace App\Services\Sat\Support;

/**
 * Normaliza el contenido de un archivo cuscar antes de enviarlo a la SAT.
 *
 * Los archivos que generan los sistemas de las navieras suelen venir en UTF-16,
 * con marca de orden de bytes y un byte nulo por cada carácter. La SAT espera
 * texto plano: si se le manda el UTF-16 tal cual, no logra leer ni el primer
 * segmento y responde que no puede obtener la información del BGM.
 *
 * En el sistema legacy la conversión ocurría por accidente, porque el archivo lo
 * descargaba el navegador con jQuery y este decodificaba según la marca antes de
 * transmitirlo. Al leer el archivo en el servidor hay que hacerla explícita.
 */
final class CuscarContent
{
    private const BOM_UTF8 = "\xEF\xBB\xBF";

    private const BOM_UTF16_LE = "\xFF\xFE";

    private const BOM_UTF16_BE = "\xFE\xFF";

    public static function prepare(string $raw): string
    {
        $content = self::toPlainText($raw);

        if (config('sat.cuscar.strip_newlines')) {
            // En EDIFACT el separador de segmentos es la comilla simple, no el
            // salto de línea. El legacy solo quitaba \n, de modo que un archivo
            // con CRLF conservaba los \r sueltos dentro del contenido enviado.
            $content = str_replace(["\r\n", "\n", "\r"], '', $content);
        }

        return $content;
    }

    /**
     * Convierte a UTF-8 sin marca de orden de bytes, venga como venga.
     */
    public static function toPlainText(string $raw): string
    {
        if (str_starts_with($raw, self::BOM_UTF16_LE)) {
            return self::fromUtf16(substr($raw, 2), 'UTF-16LE');
        }

        if (str_starts_with($raw, self::BOM_UTF16_BE)) {
            return self::fromUtf16(substr($raw, 2), 'UTF-16BE');
        }

        if (str_starts_with($raw, self::BOM_UTF8)) {
            return substr($raw, 3);
        }

        // Sin marca: un archivo UTF-16 se delata por los bytes nulos, que un
        // EDIFACT en texto plano no tiene nunca.
        if (str_contains($raw, "\x00")) {
            return self::fromUtf16($raw, self::guessUtf16Endianness($raw));
        }

        return $raw;
    }

    private static function fromUtf16(string $raw, string $from): string
    {
        // Un byte suelto al final dejaría la conversión a medias.
        if (strlen($raw) % 2 !== 0) {
            $raw = substr($raw, 0, -1);
        }

        $converted = mb_convert_encoding($raw, 'UTF-8', $from);

        // Red de seguridad: si la conversión no fuera posible, al menos que no
        // viajen bytes nulos a la SAT.
        return str_replace("\x00", '', $converted !== false ? $converted : $raw);
    }

    /**
     * En UTF-16LE el byte nulo va después del carácter; en UTF-16BE, antes.
     */
    private static function guessUtf16Endianness(string $raw): string
    {
        $muestra = substr($raw, 0, 64);
        $nulosEnPares = 0;
        $nulosEnImpares = 0;

        for ($i = 0, $largo = strlen($muestra); $i < $largo; $i++) {
            if ($muestra[$i] === "\x00") {
                $i % 2 === 0 ? $nulosEnPares++ : $nulosEnImpares++;
            }
        }

        return $nulosEnPares > $nulosEnImpares ? 'UTF-16BE' : 'UTF-16LE';
    }
}
