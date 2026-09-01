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

        if (config('sat.cuscar.transliterate')) {
            $content = self::transliterate($content);
        }

        return self::normalizeNewlines($content);
    }

    /**
     * Sustituye las letras acentuadas por su equivalente sin acento.
     *
     * Los archivos declaran sintaxis UNOA nivel A, que no admite acentos, y la
     * SAT almacena el contenido como Latin-1: una Ó enviada en UTF-8 queda
     * registrada como «Ã“» y así aparece en sus consultas y PDF. El legacy la
     * envía tal cual y sufre esa misma corrupción; transmitir «O» deja el
     * manifiesto legible. Lo que no tenga sustituto conocido viaja como «?»,
     * que sí pertenece al juego UNOA.
     */
    public static function transliterate(string $content): string
    {
        if (mb_check_encoding($content, 'ASCII')) {
            return $content;
        }

        $content = strtr($content, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'Ñ' => 'N', 'ñ' => 'n', 'Ç' => 'C', 'ç' => 'c',
            'ª' => 'a', 'º' => 'o', '°' => 'o', '´' => "'", '’' => "'",
            '“' => '"', '”' => '"', '–' => '-', '—' => '-',
        ]);

        // Cualquier otro carácter fuera de ASCII no tiene lugar en UNOA.
        return preg_replace('/[^\x00-\x7F]/u', '?', $content) ?? $content;
    }

    /**
     * El modo por omisión reproduce la cadena completa del navegador en el
     * sistema legacy, cuyos tres pasos se anulan entre sí:
     *
     *   1. replace(/\n/g,"") elimina los avances de línea; de un archivo CRLF
     *      quedan retornos de carro sueltos.
     *   2. jQuery('#contenidoArchivo').html(data) pasa por el parser HTML, que
     *      normaliza cada retorno de carro suelto a un avance de línea
     *      (HTML Standard, preprocesado del flujo de entrada).
     *   3. serializeArray() de jQuery convierte cada avance de línea en CRLF
     *      (value.replace(/\r?\n/g, "\r\n")).
     *
     * Neto: un archivo CRLF viaja a la SAT con sus saltos intactos. Una
     * simulación que se detenía en el paso 1 nos hizo transmitir retornos de
     * carro sueltos, que el analizador de la SAT no reconoce como separador de
     * línea, y rechazaba con errores de lexema y de segmento de cabecera.
     */
    private static function normalizeNewlines(string $content): string
    {
        return match (config('sat.cuscar.newline_mode')) {
            'todos' => str_replace(["\r\n", "\n", "\r"], '', $content),
            'ninguno' => $content,
            // 'crlf' y cualquier valor heredado ('solo_lf') caen aquí.
            default => str_replace("\r", "\r\n", str_replace("\n", '', $content)),
        };
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
