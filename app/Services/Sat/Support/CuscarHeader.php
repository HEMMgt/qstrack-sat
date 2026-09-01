<?php

namespace App\Services\Sat\Support;

/**
 * Datos de cabecera de un cuscar EDIFACT.
 *
 * El emisor es el más importante: la SAT exige que corresponda a la empresa con
 * cuyas credenciales se transmite. Enviar un manifiesto de una empresa
 * autenticado como otra provoca un rechazo en el segmento UNB que no dice
 * claramente cuál es el problema.
 */
final readonly class CuscarHeader
{
    public function __construct(
        public ?string $emisor = null,
        public ?string $destinatario = null,
        public ?string $fechaHora = null,
        public ?string $referencia = null,
        public ?string $numeroManifiesto = null,
        public ?string $tipoMensaje = null,
    ) {}

    /**
     * Lee la cabecera de un cuscar, venga en la codificación que venga.
     *
     * Nunca lanza: un archivo truncado o mal formado devuelve los campos que se
     * hayan podido leer y null en el resto.
     */
    public static function fromContent(string $contenido): self
    {
        $texto = CuscarContent::toPlainText($contenido);

        $unb = self::segmento($texto, 'UNB');
        $unh = self::segmento($texto, 'UNH');
        $bgm = self::segmento($texto, 'BGM');

        return new self(
            // UNB+UNOA:2+EMISOR+DESTINATARIO+FECHA:HORA+REFERENCIA+...
            emisor: self::componente($unb, 2),
            destinatario: self::componente($unb, 3),
            fechaHora: self::elemento($unb, 4),
            referencia: self::elemento($unb, 5),
            // BGM+785+NUMERO+9'
            numeroManifiesto: self::componente($bgm, 2),
            // UNH+REFERENCIA+CUSCAR:D:01A:UN'
            tipoMensaje: self::componente($unh, 2),
        );
    }

    public function emisorCoincideCon(?string $gln): bool
    {
        if (blank($gln) || blank($this->emisor)) {
            return true;
        }

        return strcasecmp(trim($gln), $this->emisor) === 0;
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'emisor' => $this->emisor,
            'destinatario' => $this->destinatario,
            'fechaHora' => $this->fechaHora,
            'referencia' => $this->referencia,
            'numeroManifiesto' => $this->numeroManifiesto,
            'tipoMensaje' => $this->tipoMensaje,
        ];
    }

    /**
     * Primer segmento del tipo pedido. Los segmentos terminan en apóstrofo y
     * pueden venir separados por saltos de línea o pegados unos a otros.
     */
    private static function segmento(string $texto, string $etiqueta): ?string
    {
        if (! preg_match('/(?:^|[\r\n\'])\s*('.$etiqueta.'\+[^\']*)/', $texto, $coincidencias)) {
            return null;
        }

        return trim($coincidencias[1]);
    }

    /** Elemento N del segmento, separados por «+». */
    private static function elemento(?string $segmento, int $posicion): ?string
    {
        if ($segmento === null) {
            return null;
        }

        $partes = explode('+', $segmento);
        $valor = trim($partes[$posicion] ?? '');

        return $valor === '' ? null : $valor;
    }

    /**
     * Primer componente de un elemento: lo que va antes del «:», por si el dato
     * trae calificador (por ejemplo `7400000000926:ZZZ`).
     */
    private static function componente(?string $segmento, int $posicion): ?string
    {
        $elemento = self::elemento($segmento, $posicion);

        if ($elemento === null) {
            return null;
        }

        $valor = trim(explode(':', $elemento)[0]);

        return $valor === '' ? null : $valor;
    }
}
