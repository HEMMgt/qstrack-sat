<?php

namespace App\Http\Requests\Sat;

use App\Rules\CuscarFileName;
use Illuminate\Foundation\Http\FormRequest;

class ValidarCuscarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sat.validar-cuscar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombreArchivo' => ['required', 'string', new CuscarFileName],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreArchivo.required' => 'Indique el nombre del archivo cuscar.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nombreArchivo' => trim((string) $this->input('nombreArchivo'))]);
    }
}
