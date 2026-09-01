<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Listeners\LogAuthenticationEvents;
use App\View\Composers\SatStatusComposer;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->forceHttpsWhenConfigured();
        $this->registerGates();

        View::composer('components.sat-status-banner', SatStatusComposer::class);

        Event::listen(Login::class, [LogAuthenticationEvents::class, 'onLogin']);
        Event::listen(Logout::class, [LogAuthenticationEvents::class, 'onLogout']);
        Event::listen(Failed::class, [LogAuthenticationEvents::class, 'onFailed']);
        Event::listen(Lockout::class, [LogAuthenticationEvents::class, 'onLockout']);
    }

    /**
     * Genera todos los enlaces y las URL de los assets en https cuando APP_URL
     * lo es.
     *
     * Detrás de un proxy o CDN que termina el TLS, la petición llega a PHP como
     * http plano: Laravel produce entonces enlaces http que el navegador bloquea
     * dentro de una página segura, y la aplicación se muestra sin estilos.
     * Se decide por APP_URL y no por APP_ENV para que valga en cualquier entorno
     * servido por https.
     */
    private function forceHttpsWhenConfigured(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Un Gate por cada caso de Permission, definido en bucle: añadir un permiso
     * al enum lo deja disponible para `can:` sin tocar este método.
     */
    private function registerGates(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user) => $user->hasPermission($permission),
            );
        }

        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }
}
