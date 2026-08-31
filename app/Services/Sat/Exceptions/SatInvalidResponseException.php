<?php

namespace App\Services\Sat\Exceptions;

/**
 * La SAT respondió 200 pero el cuerpo no es el JSON esperado: suele ser una
 * página de error HTML de su proxy, o XML si el parámetro respuestaXml se ignoró.
 */
class SatInvalidResponseException extends SatException
{
    public function userMessage(): string
    {
        return 'La SAT respondió en un formato inesperado.'.$this->reference();
    }
}
