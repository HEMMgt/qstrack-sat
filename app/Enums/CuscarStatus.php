<?php

namespace App\Enums;

enum CuscarStatus: string
{
    /** Subido y validado localmente, todavía no enviado a la SAT. */
    case Cargado = 'cargado';

    /** La SAT aceptó la transmisión y lo puso en cola. */
    case Enviado = 'enviado';

    /** La consulta de errores confirmó que fue procesado. */
    case Aceptado = 'aceptado';

    /** La SAT reportó errores en el archivo. */
    case Rechazado = 'rechazado';

    public function label(): string
    {
        return match ($this) {
            self::Cargado => 'Cargado',
            self::Enviado => 'Enviado a la SAT',
            self::Aceptado => 'Aceptado',
            self::Rechazado => 'Rechazado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cargado => 'bg-gray-100 text-gray-800',
            self::Enviado => 'bg-sky-100 text-sky-800',
            self::Aceptado => 'bg-green-100 text-green-800',
            self::Rechazado => 'bg-red-100 text-red-800',
        };
    }
}
