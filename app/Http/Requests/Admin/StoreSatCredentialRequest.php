<?php

namespace App\Http\Requests\Admin;

use App\Models\SatCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSatCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SatCredential::class) ?? false;
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
            'password' => ['required', 'string', 'max:100'],
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
            $exists = SatCredential::withTrashed()
                ->where('nit', $this->input('nit'))
                ->where('environment', $this->input('environment'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('nit', 'Ya existe una credencial con ese NIT en ese ambiente.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
