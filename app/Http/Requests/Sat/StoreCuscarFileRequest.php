<?php

namespace App\Http\Requests\Sat;

use App\Rules\CuscarFileName;
use Illuminate\Foundation\Http\FormRequest;

class StoreCuscarFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sat.agregar-cuscar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', new CuscarFileName],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Seleccione el archivo cuscar que desea enviar.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $file = $this->file('archivo');
            $max = (int) config('sat.cuscar.max_bytes');

            // En bytes exactos: la regla `max:` de Laravel trabaja en kilobytes
            // y redondea, así que dejaría pasar archivos ligeramente mayores.
            if ($file && $file->getSize() > $max) {
                $validator->errors()->add(
                    'archivo',
                    'El archivo supera el tamaño máximo de '.number_format($max).' bytes.',
                );
            }
        });
    }
}
