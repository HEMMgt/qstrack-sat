<?php

namespace App\Policies;

use App\Models\CuscarFile;
use App\Models\User;

class CuscarFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sat.agregar-cuscar') || $user->can('transacciones.view');
    }

    public function view(User $user, CuscarFile $file): bool
    {
        return $file->user_id === $user->id || $user->can('transacciones.view');
    }

    /** Descargar el archivo original. Solo su dueño; el auditor no lo necesita. */
    public function download(User $user, CuscarFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function send(User $user, CuscarFile $file): bool
    {
        return $file->user_id === $user->id
            && $user->can('sat.agregar-cuscar')
            && $user->satCredential()?->is_active === true;
    }
}
