<?php

use Illuminate\Support\Facades\URL;

it('genera enlaces y assets en https cuando APP_URL lo es', function () {
    // Detrás de un proxy o CDN que termina el TLS, la petición llega a PHP como
    // http plano. Sin forzar el esquema, Laravel genera enlaces http que el
    // navegador bloquea dentro de una página segura y la aplicación se ve sin
    // estilos.
    config()->set('app.url', 'https://ejemplo.test');
    (new App\Providers\AppServiceProvider(app()))->boot();

    expect(URL::to('/build/assets/app.css'))->toStartWith('https://')
        ->and(route('login'))->toStartWith('https://')
        ->and(route('sat.nit.create'))->toStartWith('https://');
});
