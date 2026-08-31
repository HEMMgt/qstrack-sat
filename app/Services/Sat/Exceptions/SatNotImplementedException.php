<?php

namespace App\Services\Sat\Exceptions;

/** Endpoint documentado por la SAT que este sistema todavía no expone. */
class SatNotImplementedException extends SatException
{
    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
