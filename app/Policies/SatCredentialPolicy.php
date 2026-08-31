<?php

namespace App\Policies;

use App\Models\SatCredential;
use App\Models\User;

class SatCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('credenciales.manage');
    }

    public function view(User $user, SatCredential $credential): bool
    {
        return $user->can('credenciales.manage')
            || $user->satCredential()?->is($credential) === true;
    }

    public function create(User $user): bool
    {
        return $user->can('credenciales.manage');
    }

    public function update(User $user, SatCredential $credential): bool
    {
        return $user->can('credenciales.manage');
    }

    public function delete(User $user, SatCredential $credential): bool
    {
        return $user->can('credenciales.manage');
    }

    /**
     * Rotar el NIT y la contraseña de la credencial.
     *
     * Solo el dueño de la credencial (o un administrador). Esto es lo que cierra
     * el fallo del sistema legacy, donde sat/grabar_clave.php actualizaba
     * cualquier fila cuyo id llegara en el POST, sin comprobar a quién pertenecía.
     */
    public function rotateSecret(User $user, SatCredential $credential): bool
    {
        return $user->satCredential()?->is($credential) === true;
    }
}
