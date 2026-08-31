<?php

namespace App\Models;

use App\Services\Sat\SatEndpoint;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una llamada al servicio de la SAT.
 *
 * Se crea antes del request y se completa después, pase lo que pase: es la única
 * evidencia de lo que se envió y lo que contestaron.
 */
class SatTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\SatTransactionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'user_id',
        'sat_credential_id',
        'endpoint',
        'environment',
        'base_url',
        'request_payload',
        'http_status',
        'duration_ms',
        'attempts',
        'succeeded',
        'response_type',
        'response_description',
        'response_manifiesto',
        'response_raw',
        'error_class',
        'error_message',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'endpoint' => SatEndpoint::class,
            'request_payload' => 'array',
            'response_manifiesto' => 'array',
            'succeeded' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(SatCredential::class, 'sat_credential_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
