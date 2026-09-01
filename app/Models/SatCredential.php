<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Credencial de una empresa transmisora ante la SAT: el NIT y su contraseña.
 *
 * La contraseña nunca debe llegar a una vista ni a un log. El único lugar que
 * la lee es SatClient, para armar el cuerpo del POST.
 */
class SatCredential extends Model
{
    /** @use HasFactory<\Database\Factories\SatCredentialFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'nit',
        'gln',
        'password',
        'environment',
        'is_active',
        'notes',
        'created_by',
        'secret_rotated_at',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'secret_rotated_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sat_credential_user')
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Etiqueta para listados: nombre de la empresa y su NIT, nunca la clave. */
    public function label(): string
    {
        return "{$this->name} ({$this->nit})";
    }

    /**
     * Comprueba que el cuscar corresponde a esta empresa.
     *
     * Se rechaza en dos casos: cuando esta credencial declara un emisor
     * distinto, y cuando el emisor del archivo pertenece a otra credencial
     * registrada. Lo segundo cubre el caso real que se dio en producción, donde
     * la credencial en uso no tenía código capturado pero sí lo tenía la que
     * correspondía al archivo.
     */
    public function admiteEmisor(?string $emisor): bool
    {
        if (blank($emisor)) {
            return true;
        }

        if (filled($this->gln)) {
            return strcasecmp(trim($this->gln), trim($emisor)) === 0;
        }

        return ! self::deEmisor($emisor, exceptoId: $this->id) instanceof self;
    }

    /** Credencial registrada para ese código de emisor, si existe alguna. */
    public static function deEmisor(string $emisor, ?int $exceptoId = null): ?self
    {
        return self::query()
            ->whereRaw('LOWER(gln) = ?', [mb_strtolower(trim($emisor))])
            ->when($exceptoId, fn ($q) => $q->whereKeyNot($exceptoId))
            ->first();
    }
}
