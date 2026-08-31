<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Bitácora de acciones sensibles.
 *
 * Registra quién, qué, sobre qué registro, desde dónde y cuándo. Ninguna
 * operación SAT quedaba registrada en el sistema legacy.
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description ? Str::limit($description, 250) : null,
            'properties' => self::scrub($properties) ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Str::limit((string) Request::userAgent(), 250, ''),
            'created_at' => now(),
        ]);
    }

    /**
     * Última red de seguridad: aunque quien llama no debería pasar secretos,
     * cualquier clave que se parezca a una contraseña se sustituye.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private static function scrub(array $properties): array
    {
        $sensitive = ['password', 'clave', 'secret', 'token'];

        array_walk_recursive($properties, function (&$value, $key) use ($sensitive) {
            foreach ($sensitive as $needle) {
                if (is_string($key) && str_contains(strtolower($key), $needle)) {
                    $value = '***';

                    return;
                }
            }
        });

        return $properties;
    }
}
