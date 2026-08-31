<?php

namespace App\Http\Requests\Sat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMySatCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La credencial se resuelve desde el usuario en sesión, nunca desde un
        // id enviado en el formulario. Ese era el fallo de sat/grabar_clave.php,
        // que actualizaba cualquier fila cuyo id llegara en el POST.
        $credential = $this->user()?->satCredential();

        return $credential !== null
            && ($this->user()?->can('sat.cambiar-clave') ?? false)
            && $this->user()->can('rotateSecret', $credential);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nit' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z\-]+$/'],
            'password' => ['required', 'string', 'min:4', 'max:100', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'nit.regex' => 'El NIT solo puede contener números, letras y guiones.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nit' => trim((string) $this->input('nit'))]);
    }
}
