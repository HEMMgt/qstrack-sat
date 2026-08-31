<?php

namespace App\Services\Sat\Exceptions;

/** La SAT no respondió: caída de red, timeout, o un error del servidor. */
class SatUnavailableException extends SatException
{
    public function userMessage(): string
    {
        return 'No fue posible comunicarse con el servicio de la SAT. '
            .'Intente de nuevo en unos minutos.'.$this->reference();
    }
}
