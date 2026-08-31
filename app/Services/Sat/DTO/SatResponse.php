<?php

namespace App\Services\Sat\DTO;

use App\Services\Sat\SatEndpoint;

/**
 * Respuesta ya interpretada de la SAT.
 *
 * `tipo` vale "EXITO" cuando la operación salió bien; cualquier otro valor es un
 * rechazo del lado de la SAT, que no es lo mismo que un fallo de comunicación
 * (eso último llega como excepción, no como SatResponse).
 */
final readonly class SatResponse
{
    public function __construct(
        public SatEndpoint $endpoint,
        public string $tipo,
        public string $descripcion,
        public Manifiesto $manifiesto,
        public int $httpStatus,
        public string $transactionUuid,
        public ?int $transactionId = null,
        public array $rawJson = [],
    ) {}

    public function isSuccess(): bool
    {
        return strtoupper(trim($this->tipo)) === 'EXITO';
    }
}
