<?php

namespace App\Services\Sat\Exceptions;

use RuntimeException;

/** El usuario intentó una operación SAT sin tener credencial asignada. */
class MissingSatCredentialException extends RuntimeException
{
    public function userMessage(): string
    {
        return 'No tiene una credencial SAT asignada. Solicítela a un administrador.';
    }
}
