<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Formato del nombre de un archivo cuscar según la SAT: TCCCNNNN.JJJ
 *
 *   posición 0      tipo de servicio, P o E
 *   posiciones 4-7  correlativo numérico de cuatro dígitos
 *   posición 8      punto
 *   posiciones 9-11 fecha juliana, tres dígitos
 *
 * Son las mismas reglas que aplicaba sat/grabar_archivo.php en el sistema
 * legacy, reunidas en un solo lugar y con mensajes en español.
 */
class CuscarFileName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = $value instanceof UploadedFile
            ? $value->getClientOriginalName()
            : (string) $value;

        // basename() corta cualquier intento de recorrer directorios antes de
        // que las demás comprobaciones puedan darlo por bueno.
        if ($name !== basename($name)) {
            $fail('El nombre del archivo no puede contener rutas.');

            return;
        }

        if (mb_strlen($name) !== 12) {
            $fail('El nombre debe tener exactamente 12 caracteres: 8 del nombre, el punto y 3 de la extensión.');

            return;
        }

        if ($name[8] !== '.') {
            $fail('El nombre debe tener el formato TCCCNNNN.JJJ, con el punto en la novena posición.');

            return;
        }

        if (! in_array(strtoupper($name[0]), (array) config('sat.cuscar.service_types'), true)) {
            $fail('El primer carácter debe ser P o E, según el tipo de servicio.');
        }

        // substr($name, 4, 4) son los caracteres 5 al 8, con índices desde cero;
        // es lo mismo que hacía sat/grabar_archivo.php. No lo cambie por
        // intuición: la SAT espera el correlativo justo en esa posición.
        if (! preg_match('/^\d{4}$/', substr($name, 4, 4))) {
            $fail('Los caracteres 5 al 8 deben ser el correlativo numérico de cuatro dígitos.');
        }

        if (! preg_match('/^\d{3}$/', substr($name, 9, 3))) {
            $fail('La extensión debe ser la fecha juliana: tres dígitos.');
        }
    }

    /**
     * Descompone un nombre ya validado en sus partes.
     *
     * @return array{service_type: string, correlativo: string, julian_extension: string}
     */
    public static function parse(string $name): array
    {
        return [
            'service_type' => strtoupper($name[0]),
            'correlativo' => substr($name, 4, 4),
            'julian_extension' => substr($name, 9, 3),
        ];
    }
}
