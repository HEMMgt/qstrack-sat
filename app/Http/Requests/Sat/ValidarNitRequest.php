<?php

namespace App\Http\Requests\Sat;

use Illuminate\Foundation\Http\FormRequest;

class ValidarNitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sat.validar-nit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nit' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z\-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nit.regex' => 'El NIT solo puede contener números, letras y guiones.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nit' => trim((string) $this->input('nit'))]);
    }
}
