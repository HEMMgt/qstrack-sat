<?php

namespace App\Services\Sat;

use App\Models\SatCredential;
use App\Models\SatTransaction;
use App\Models\User;
use App\Services\Sat\DTO\Manifiesto;
use App\Services\Sat\DTO\SatResponse;
use App\Services\Sat\Exceptions\SatInvalidResponseException;
use App\Services\Sat\Exceptions\SatNotImplementedException;
use App\Services\Sat\Exceptions\SatUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cliente del servicio de manifiestos de la SAT.
 *
 * Todas las llamadas salen desde el servidor. En el sistema legacy la petición
 * la hacía el navegador del usuario con el NIT y la contraseña escritos en
 * campos ocultos del HTML, así que cualquiera podía leerlos viendo el código
 * fuente de la página.
 *
 * Todo método público desemboca en call(), que abre el registro de la
 * transacción antes del request y lo cierra en un finally: no existe forma de
 * llamar a la SAT sin dejar rastro, ni siquiera cuando revienta.
 */
class SatClient
{
    public function __construct(
        private readonly SatCredential $credential,
        private readonly ?User $actor = null,
        private readonly ?string $ipAddress = null,
    ) {}

    public function validarNit(string $nit): SatResponse
    {
        return $this->call(SatEndpoint::ValidarNit, ['nit' => trim($nit)]);
    }

    public function consultarErroresCuscar(string $nombreArchivo): SatResponse
    {
        return $this->call(SatEndpoint::ConsultarErroresCuscar, [
            'nombreArchivo' => trim($nombreArchivo),
        ]);
    }

    public function ingresarCuscar(
        string $nombreArchivo,
        string $contenidoArchivo,
        ?bool $sincrono = null,
    ): SatResponse {
        return $this->call(SatEndpoint::IngresarCuscar, [
            'nombreArchivo' => trim($nombreArchivo),
            'contenidoArchivo' => $contenidoArchivo,
            'procesamientoSincrono' => $sincrono === null
                ? config('sat.cuscar.procesamiento_sincrono')
                : ($sincrono ? 'true' : 'false'),
        ]);
    }

    public function consultarEncabezadoManifiesto(string $numeroManifiesto): SatResponse
    {
        return $this->call(SatEndpoint::ConsultarEncabezadoManifiesto, [
            'numeroManifiesto' => trim($numeroManifiesto),
        ]);
    }

    /**
     * Documentado por la SAT, sin pantalla todavía. Añadirlo es escribir el
     * método, un FormRequest y una vista: call() no se toca.
     */
    public function solicitarCuscar(array $params = []): SatResponse
    {
        throw new SatNotImplementedException(
            'La solicitud de archivos cuscar aún no está disponible en este sistema.',
        );
    }

    public function consultarManifiestosValidados(array $params = []): SatResponse
    {
        throw new SatNotImplementedException(
            'La consulta de manifiestos validados aún no está disponible en este sistema.',
        );
    }

    /**
     * Punto único de salida hacia la SAT.
     *
     * @param  array<string, string>  $params
     */
    private function call(SatEndpoint $endpoint, array $params): SatResponse
    {
        $payload = [
            'usuario' => $this->credential->nit,
            'password' => $this->credential->password,
            'respuestaXml' => 'false',
        ] + $params;

        $transaction = $this->openTransaction($endpoint, $payload);

        $startedAt = hrtime(true);
        $attempts = 1;
        $response = null;
        $failure = null;

        try {
            $response = $this->send($endpoint, $payload, $attempts);

            return $this->interpret($endpoint, $response, $transaction);
        } catch (ConnectionException $e) {
            $failure = new SatUnavailableException(
                message: $e->getMessage(),
                transactionUuid: $transaction->uuid,
            );

            throw $failure;
        } catch (Throwable $e) {
            $failure = $e;

            throw $e;
        } finally {
            $this->closeTransaction(
                transaction: $transaction,
                response: $response,
                failure: $failure,
                attempts: $attempts,
                durationMs: (int) ((hrtime(true) - $startedAt) / 1_000_000),
            );
        }
    }

    /**
     * @param  array<string, string>  $payload
     */
    private function send(SatEndpoint $endpoint, array $payload, int &$attempts): Response
    {
        $retryTimes = $endpoint->isIdempotent()
            ? max(1, (int) config('sat.retry.times'))
            : 1;

        return Http::asForm()
            ->timeout((int) config('sat.timeout'))
            ->connectTimeout((int) config('sat.connect_timeout'))
            ->withOptions(['verify' => (bool) config('sat.verify_ssl')])
            ->retry(
                $retryTimes,
                (int) config('sat.retry.sleep_ms'),
                // Solo se reintenta por fallo de conexión. Un 4xx o 5xx es una
                // respuesta de la SAT y repetirla no la va a cambiar; en
                // ingresarCuscar además podría duplicar el manifiesto.
                function (Throwable $exception) use (&$attempts): bool {
                    if ($exception instanceof ConnectionException) {
                        $attempts++;

                        return true;
                    }

                    return false;
                },
                throw: false,
            )
            ->post($this->url($endpoint), $payload);
    }

    private function interpret(
        SatEndpoint $endpoint,
        Response $response,
        SatTransaction $transaction,
    ): SatResponse {
        if ($response->failed()) {
            throw new SatUnavailableException(
                message: "La SAT respondió con HTTP {$response->status()}.",
                transactionUuid: $transaction->uuid,
                httpStatus: $response->status(),
            );
        }

        $json = $this->decode($response);
        $envelope = $json['manifiestoRespuesta'] ?? null;

        if (! is_array($envelope) || ! is_array($envelope['respuesta'] ?? null)) {
            throw new SatInvalidResponseException(
                message: 'La respuesta no contiene manifiestoRespuesta.respuesta.',
                transactionUuid: $transaction->uuid,
                httpStatus: $response->status(),
            );
        }

        return new SatResponse(
            endpoint: $endpoint,
            tipo: (string) ($envelope['respuesta']['tipo'] ?? ''),
            descripcion: (string) ($envelope['respuesta']['descripcion'] ?? ''),
            manifiesto: Manifiesto::fromArray($envelope['manifiesto'] ?? []),
            httpStatus: $response->status(),
            transactionUuid: $transaction->uuid,
            transactionId: $transaction->id,
            rawJson: $json,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        try {
            $json = $response->json();
        } catch (Throwable) {
            $json = null;
        }

        return is_array($json) ? $json : [];
    }

    private function url(SatEndpoint $endpoint): string
    {
        return config('sat.base_url').$endpoint->value;
    }

    /**
     * @param  array<string, string>  $payload
     */
    private function openTransaction(SatEndpoint $endpoint, array $payload): SatTransaction
    {
        return SatTransaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $this->actor?->id,
            'sat_credential_id' => $this->credential->id,
            'endpoint' => $endpoint,
            'environment' => (string) config('sat.environment'),
            'base_url' => (string) config('sat.base_url'),
            'request_payload' => $this->redactPayload($payload),
            'ip_address' => $this->ipAddress,
            'created_at' => now(),
        ]);
    }

    private function closeTransaction(
        SatTransaction $transaction,
        ?Response $response,
        ?Throwable $failure,
        int $attempts,
        int $durationMs,
    ): void {
        $attributes = [
            'duration_ms' => $durationMs,
            'attempts' => min($attempts, 255),
        ];

        if ($response !== null) {
            $envelope = $this->decode($response)['manifiestoRespuesta'] ?? [];
            $respuesta = is_array($envelope['respuesta'] ?? null) ? $envelope['respuesta'] : [];
            $manifiesto = is_array($envelope['manifiesto'] ?? null) ? $envelope['manifiesto'] : [];

            $attributes += [
                'http_status' => $response->status(),
                'response_type' => $respuesta['tipo'] ?? null,
                'response_description' => $respuesta['descripcion'] ?? null,
                'response_manifiesto' => $manifiesto !== []
                    ? Manifiesto::fromArray($manifiesto)->toArray()
                    : null,
                'succeeded' => $failure === null
                    && strtoupper(trim((string) ($respuesta['tipo'] ?? ''))) === 'EXITO',
            ];

            if (config('sat.logging.store_raw_response')) {
                $attributes['response_raw'] = Str::limit(
                    $response->body(),
                    (int) config('sat.logging.raw_max_chars'),
                    ' […truncado]',
                );
            }
        }

        if ($failure !== null) {
            $attributes += [
                'succeeded' => false,
                'error_class' => class_basename($failure),
                'error_message' => Str::limit($failure->getMessage(), 2000),
            ];
        }

        $transaction->forceFill($attributes)->save();
    }

    /**
     * Nunca guardamos la contraseña de la SAT, ni el contenido completo del
     * cuscar (que ya está en disco y pesaría de más en la base).
     *
     * @param  array<string, string>  $payload
     * @return array<string, string>
     */
    private function redactPayload(array $payload): array
    {
        foreach ((array) config('sat.logging.redacted_keys') as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '***';
            }
        }

        if (isset($payload['contenidoArchivo'])) {
            $payload['contenidoArchivo'] = '<'.strlen($payload['contenidoArchivo']).' bytes>';
        }

        return $payload;
    }
}
