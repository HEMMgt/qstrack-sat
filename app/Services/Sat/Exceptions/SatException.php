<?php

namespace App\Services\Sat\Exceptions;

use RuntimeException;

/**
 * Base de los fallos al hablar con la SAT.
 *
 * Lleva el uuid de la transacción para que el mensaje que ve el usuario permita
 * ubicar la llamada exacta en el historial.
 */
abstract class SatException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $transactionUuid = null,
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct($message);
    }

    /** Mensaje apto para mostrarle al usuario final. */
    abstract public function userMessage(): string;

    protected function reference(): string
    {
        return $this->transactionUuid
            ? " Referencia: {$this->transactionUuid}."
            : '';
    }
}
