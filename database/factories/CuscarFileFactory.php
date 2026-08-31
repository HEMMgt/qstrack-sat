<?php

namespace Database\Factories;

use App\Enums\CuscarStatus;
use App\Models\CuscarFile;
use App\Models\SatCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CuscarFile>
 */
class CuscarFileFactory extends Factory
{
    protected $model = CuscarFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = 'P001'.fake()->numerify('####').'.'.fake()->numerify('###');

        return [
            'user_id' => User::factory(),
            'sat_credential_id' => SatCredential::factory(),
            'filename' => $filename,
            'service_type' => 'P',
            'correlativo' => substr($filename, 4, 4),
            'julian_extension' => substr($filename, 9, 3),
            'size_bytes' => 1024,
            'sha256' => hash('sha256', $filename),
            'storage_path' => '1/'.$filename,
            'status' => CuscarStatus::Cargado,
        ];
    }

    public function enviado(): static
    {
        return $this->state(fn () => [
            'status' => CuscarStatus::Enviado,
            'sent_at' => now(),
        ]);
    }
}
