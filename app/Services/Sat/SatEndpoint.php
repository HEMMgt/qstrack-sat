<?php

namespace App\Services\Sat;

/**
 * Métodos del servicio de manifiestos de la SAT.
 *
 * El valor de cada caso es el segmento de URL que se concatena a la URL base.
 */
enum SatEndpoint: string
{
    case Probar = 'probar';
    case ValidarNit = 'validarNit';
    case ConsultarErroresCuscar = 'consultarErroresCuscar';
    case IngresarCuscar = 'ingresarCuscar';
    case ConsultarEncabezadoManifiesto = 'consultarEncabezadoManifiesto';

    // Documentados por la SAT, todavía sin pantalla en el sistema.
    case SolicitarCuscar = 'solicitarCuscar';
    case ConsultarManifiestosValidados = 'consultarManifiestosValidados';

    public function label(): string
    {
        return match ($this) {
            self::Probar => 'Verificar disponibilidad',
            self::ValidarNit => 'Validar NIT',
            self::ConsultarErroresCuscar => 'Validar cuscar',
            self::IngresarCuscar => 'Enviar cuscar',
            self::ConsultarEncabezadoManifiesto => 'Consultar manifiesto',
            self::SolicitarCuscar => 'Solicitar cuscar',
            self::ConsultarManifiestosValidados => 'Consultar manifiestos validados',
        };
    }

    public function isImplemented(): bool
    {
        return ! in_array($this, [
            self::SolicitarCuscar,
            self::ConsultarManifiestosValidados,
        ], true);
    }

    /**
     * `ingresarCuscar` cambia estado en la SAT: un reintento automático podría
     * dar de alta el mismo manifiesto dos veces.
     */
    public function isIdempotent(): bool
    {
        return $this !== self::IngresarCuscar;
    }
}
