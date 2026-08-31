<?php

namespace App\Services\Sat\DTO;

/**
 * El objeto `manifiesto` de la respuesta de la SAT.
 *
 * Todos los campos son opcionales y de tipo texto a propósito: la SAT devuelve
 * cadena vacía en los que no aplican al endpoint consultado, y el formato de las
 * fechas no está documentado ni es estable.
 */
final readonly class Manifiesto
{
    public function __construct(
        public ?string $nombreCuscar = null,
        public ?string $numeroManifiesto = null,
        public ?string $fechaRecepcion = null,
        public ?string $firmaElectronica = null,
        public ?string $tipoMensaje = null,
        public ?string $funcionMensaje = null,
        public ?string $estado = null,
        public ?string $estadoDictamen = null,
        public ?string $tipoOperacion = null,
        public ?string $empresaTransmisora = null,
        public ?string $numeroViajeVuelo = null,
        public ?string $nombreMedioTransporte = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $get = static function (string $key) use ($data): ?string {
            $value = $data[$key] ?? null;

            if (! is_scalar($value)) {
                return null;
            }

            $value = trim((string) $value);

            return $value === '' ? null : $value;
        };

        return new self(
            nombreCuscar: $get('nombreCuscar'),
            numeroManifiesto: $get('numeroManifiesto'),
            fechaRecepcion: $get('fechaRecepcion'),
            firmaElectronica: $get('firmaElectronica'),
            tipoMensaje: $get('tipoMensaje'),
            funcionMensaje: $get('funcionMensaje'),
            estado: $get('estado'),
            estadoDictamen: $get('estadoDictamen'),
            tipoOperacion: $get('tipoOperacion'),
            empresaTransmisora: $get('empresaTransmisora'),
            numeroViajeVuelo: $get('numeroViajeVuelo'),
            nombreMedioTransporte: $get('nombreMedioTransporte'),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'nombreCuscar' => $this->nombreCuscar,
            'numeroManifiesto' => $this->numeroManifiesto,
            'fechaRecepcion' => $this->fechaRecepcion,
            'firmaElectronica' => $this->firmaElectronica,
            'tipoMensaje' => $this->tipoMensaje,
            'funcionMensaje' => $this->funcionMensaje,
            'estado' => $this->estado,
            'estadoDictamen' => $this->estadoDictamen,
            'tipoOperacion' => $this->tipoOperacion,
            'empresaTransmisora' => $this->empresaTransmisora,
            'numeroViajeVuelo' => $this->numeroViajeVuelo,
            'nombreMedioTransporte' => $this->nombreMedioTransporte,
        ];
    }

    /**
     * Etiquetas legibles para las pantallas de resultado, en el orden en que se
     * presentan.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'numeroManifiesto' => 'Número de manifiesto',
            'nombreCuscar' => 'Nombre del cuscar',
            'fechaRecepcion' => 'Fecha de recepción',
            'estado' => 'Estado',
            'estadoDictamen' => 'Estado del dictamen',
            'tipoOperacion' => 'Tipo de operación',
            'tipoMensaje' => 'Tipo de mensaje',
            'funcionMensaje' => 'Función del mensaje',
            'empresaTransmisora' => 'Empresa transmisora',
            'numeroViajeVuelo' => 'Número de viaje o vuelo',
            'nombreMedioTransporte' => 'Medio de transporte',
            'firmaElectronica' => 'Firma electrónica',
        ];
    }

    public function isEmpty(): bool
    {
        return collect($this->toArray())->filter()->isEmpty();
    }
}
