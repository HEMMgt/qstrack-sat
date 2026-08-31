<?php

namespace App\Models;

use App\Enums\CuscarStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Archivo cuscar cargado por un usuario.
 *
 * El archivo vive en el disco privado `cuscar`. El sistema legacy lo dejaba en
 * uploads/cuscar/, servido públicamente, y el navegador lo volvía a descargar
 * por URL para poder enviarlo.
 */
class CuscarFile extends Model
{
    /** @use HasFactory<\Database\Factories\CuscarFileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sat_credential_id',
        'filename',
        'service_type',
        'correlativo',
        'julian_extension',
        'size_bytes',
        'sha256',
        'storage_path',
        'status',
        'sent_at',
        'sat_transaction_id',
        'numero_manifiesto',
        'fecha_recepcion',
        'firma_electronica',
        'last_response_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CuscarStatus::class,
            'sent_at' => 'datetime',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SatTransaction::class, 'sat_transaction_id');
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function disk()
    {
        return Storage::disk(config('sat.cuscar.disk'));
    }

    /** Contenido tal como está en disco, sin normalizar. */
    public function contents(): string
    {
        return (string) $this->disk()->get($this->storage_path);
    }

    public function exists(): bool
    {
        return $this->disk()->exists($this->storage_path);
    }

    public function wasSent(): bool
    {
        return $this->status !== CuscarStatus::Cargado;
    }
}
