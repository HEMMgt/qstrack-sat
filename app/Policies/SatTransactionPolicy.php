<?php

namespace App\Policies;

use App\Models\SatTransaction;
use App\Models\User;

class SatTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transacciones.view') || $user->satCredential() !== null;
    }

    public function view(User $user, SatTransaction $transaction): bool
    {
        return $transaction->user_id === $user->id
            || $user->can('transacciones.view');
    }
}
