<?php

namespace App\Models;

use App\Services\Sat\DTO\Manifiesto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Copia de un encabezado de manifiesto tal como lo devolvió la SAT.
 *
 * Es un histórico, no un catálogo: cada consulta deja su propia fila, de modo
 * que se puede ver cómo cambió el estado de un manifiesto con el tiempo.
 */
class SatManifest extends Model
{
    /** @use HasFactory<\Database\Factories\SatManifestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sat_credential_id',
        'sat_transaction_id',
        'numero_manifiesto_consultado',
        'nombre_cuscar',
        'numero_manifiesto',
        'fecha_recepcion',
        'firma_electronica',
        'tipo_mensaje',
        'funcion_mensaje',
        'estado',
        'estado_dictamen',
        'tipo_operacion',
        'empresa_transmisora',
        'numero_viaje_vuelo',
        'nombre_medio_transporte',
        'queried_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['queried_at' => 'datetime'];
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

    /**
     * Traduce los nombres del DTO (camelCase, como los manda la SAT) a las
     * columnas de esta tabla.
     *
     * @return array<string, string|null>
     */
    public static function attributesFromManifiesto(Manifiesto $manifiesto): array
    {
        return [
            'nombre_cuscar' => $manifiesto->nombreCuscar,
            'numero_manifiesto' => $manifiesto->numeroManifiesto,
            'fecha_recepcion' => $manifiesto->fechaRecepcion,
            'firma_electronica' => $manifiesto->firmaElectronica,
            'tipo_mensaje' => $manifiesto->tipoMensaje,
            'funcion_mensaje' => $manifiesto->funcionMensaje,
            'estado' => $manifiesto->estado,
            'estado_dictamen' => $manifiesto->estadoDictamen,
            'tipo_operacion' => $manifiesto->tipoOperacion,
            'empresa_transmisora' => $manifiesto->empresaTransmisora,
            'numero_viaje_vuelo' => $manifiesto->numeroViajeVuelo,
            'nombre_medio_transporte' => $manifiesto->nombreMedioTransporte,
        ];
    }

    /**
     * Los mismos datos en forma de DTO, para reutilizar las etiquetas de la
     * vista de resultado.
     */
    public function toManifiesto(): Manifiesto
    {
        return new Manifiesto(
            nombreCuscar: $this->nombre_cuscar,
            numeroManifiesto: $this->numero_manifiesto,
            fechaRecepcion: $this->fecha_recepcion,
            firmaElectronica: $this->firma_electronica,
            tipoMensaje: $this->tipo_mensaje,
            funcionMensaje: $this->funcion_mensaje,
            estado: $this->estado,
            estadoDictamen: $this->estado_dictamen,
            tipoOperacion: $this->tipo_operacion,
            empresaTransmisora: $this->empresa_transmisora,
            numeroViajeVuelo: $this->numero_viaje_vuelo,
            nombreMedioTransporte: $this->nombre_medio_transporte,
        );
    }
}
