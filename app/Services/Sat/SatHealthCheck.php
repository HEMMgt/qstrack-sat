<?php

namespace App\Services\Sat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Comprueba que el servicio de la SAT responde.
 *
 * Con timeout corto y resultado cacheado: el sistema legacy hacía un
 * file_get_contents sin timeout en cada carga de pantalla, de modo que una
 * caída de la SAT dejaba colgada la interfaz hasta agotar el tiempo de PHP.
 * Aquí un fallo se traduce en un aviso, nunca en un bloqueo.
 */
class SatHealthCheck
{
    public function isUp(): bool
    {
        return Cache::remember(
            $this->cacheKey(),
            (int) config('sat.healthcheck.ttl'),
            fn () => $this->probe(),
        );
    }

    /** Consulta sin pasar por caché; para el comando de consola. */
    public function probe(): bool
    {
        try {
            $response = Http::timeout((int) config('sat.healthcheck.timeout'))
                ->withOptions(['verify' => (bool) config('sat.verify_ssl')])
                ->get(config('sat.base_url').SatEndpoint::Probar->value);

            return $response->successful()
                && trim($response->body()) === config('sat.healthcheck.expected_body');
        } catch (Throwable) {
            return false;
        }
    }

    public function forget(): void
    {
        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        return 'sat.health.'.md5((string) config('sat.base_url'));
    }
}
