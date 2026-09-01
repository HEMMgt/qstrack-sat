<?php

namespace App\Http\Requests\Admin;

use App\Models\SatCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSatCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('credential')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'nit' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z\-]+$/'],
            'gln' => ['nullable', 'string', 'max:35', 'regex:/^[0-9A-Za-z\-]+$/'],
            // Vacío = conservar la contraseña actual.
            'password' => ['nullable', 'string', 'max:100'],
            'environment' => ['required', Rule::in(['pruebas', 'produccion'])],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nit.regex' => 'El NIT solo puede contener números, letras y guiones.',
            'gln.regex' => 'El código de emisor solo puede contener números, letras y guiones.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $credential = $this->route('credential');

            $exists = SatCredential::withTrashed()
                ->where('nit', $this->input('nit'))
                ->where('environment', $this->input('environment'))
                ->whereKeyNot($credential->getKey())
                ->exists();

            if ($exists) {
                $validator->errors()->add('nit', 'Ya existe otra credencial con ese NIT en ese ambiente.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
