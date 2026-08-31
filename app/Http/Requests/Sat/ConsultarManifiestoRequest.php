<?php

namespace App\Http\Requests\Sat;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarManifiestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sat.consultar-manifiesto') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numeroManifiesto' => ['required', 'string', 'max:60', 'regex:/^[0-9A-Za-z\-\/]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'numeroManifiesto.required' => 'Indique el número de manifiesto.',
            'numeroManifiesto.regex' => 'El número de manifiesto tiene caracteres no permitidos.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['numeroManifiesto' => trim((string) $this->input('numeroManifiesto'))]);
    }
}
