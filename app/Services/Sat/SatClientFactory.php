<?php

namespace App\Services\Sat;

use App\Models\SatCredential;
use App\Models\User;
use App\Services\Sat\Exceptions\MissingSatCredentialException;
use Illuminate\Http\Request;

/**
 * Construye clientes SAT. Los controladores dependen de esta fábrica y no del
 * cliente, porque quién es el usuario y qué credencial usa se resuelve aquí.
 */
class SatClientFactory
{
    public function __construct(private readonly Request $request) {}

    public function forUser(User $user): SatClient
    {
        $credential = $user->satCredential();

        if ($credential === null) {
            throw new MissingSatCredentialException(
                "El usuario {$user->email} no tiene credencial SAT asignada.",
            );
        }

        return $this->forCredential($credential, $user);
    }

    public function forCredential(SatCredential $credential, ?User $actor = null): SatClient
    {
        return new SatClient(
            credential: $credential,
            actor: $actor,
            ipAddress: $this->request->ip(),
        );
    }
}
