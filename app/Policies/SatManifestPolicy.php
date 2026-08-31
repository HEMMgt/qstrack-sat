<?php

namespace App\Policies;

use App\Models\SatManifest;
use App\Models\User;

class SatManifestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sat.consultar-manifiesto') || $user->can('transacciones.view');
    }

    public function view(User $user, SatManifest $manifest): bool
    {
        return $manifest->user_id === $user->id
            || $user->can('transacciones.view');
    }
}
