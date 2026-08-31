<?php

namespace Database\Factories;

use App\Models\SatCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SatCredential>
 */
class SatCredentialFactory extends Factory
{
    protected $model = SatCredential::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'nit' => (string) fake()->unique()->numberBetween(1_000_000, 99_999_999),
            'password' => 'clave-sat-'.fake()->word(),
            'environment' => 'pruebas',
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function production(): static
    {
        return $this->state(fn () => ['environment' => 'produccion']);
    }
}
