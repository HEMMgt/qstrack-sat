<?php

namespace Database\Factories;

use App\Models\SatManifest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SatManifest>
 */
class SatManifestFactory extends Factory
{
    protected $model = SatManifest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $numero = 'GT-'.fake()->numberBetween(1000, 9999);

        return [
            'numero_manifiesto_consultado' => $numero,
            'numero_manifiesto' => $numero,
            'nombre_cuscar' => 'P0011234.123',
            'fecha_recepcion' => now()->format('Y-m-d H:i:s'),
            'estado' => 'RECIBIDO',
            'estado_dictamen' => 'SIN DICTAMEN',
            'tipo_operacion' => 'IMPORTACION',
            'empresa_transmisora' => fake()->company(),
            'queried_at' => now(),
        ];
    }
}
