<?php

namespace App\Listeners;

use App\Support\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Los métodos se llaman onX y no handleX a propósito: Laravel descubre solos los
 * que empiezan por "handle", y quedarían registrados dos veces junto al
 * Event::listen explícito del AppServiceProvider.
 */
class LogAuthenticationEvents
{
    public function onLogin(Login $event): void
    {
        AuditLogger::log('auth.login', $event->user, 'Ingreso al sistema', userId: $event->user->getKey());
    }

    public function onLogout(Logout $event): void
    {
        AuditLogger::log('auth.logout', $event->user, 'Cierre de sesión', userId: $event->user?->getKey());
    }

    public function onFailed(Failed $event): void
    {
        AuditLogger::log(
            'auth.failed',
            $event->user,
            'Intento de ingreso fallido',
            ['email' => $event->credentials['email'] ?? null],
            userId: $event->user?->getKey(),
        );
    }

    public function onLockout(Lockout $event): void
    {
        AuditLogger::log('auth.lockout', null, 'Demasiados intentos de ingreso', [
            'email' => $event->request->input('email'),
        ]);
    }
}
