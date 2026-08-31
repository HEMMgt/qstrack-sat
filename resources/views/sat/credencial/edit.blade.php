<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">SAT — Cambiar clave</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-alert />

                @if ($credential === null)
                    <p class="text-sm text-gray-600">
                        No tiene una credencial SAT asignada, así que no puede usar las opciones de SAT.
                        Solicite a un administrador que le asigne una.
                    </p>
                @else
                    <dl class="mb-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Empresa</dt>
                            <dd class="text-gray-900">{{ $credential->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Ambiente</dt>
                            <dd class="text-gray-900">{{ ucfirst($credential->environment) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Última rotación</dt>
                            <dd class="text-gray-900">{{ $credential->secret_rotated_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
                        </div>
                    </dl>

                    <form method="POST" action="{{ route('sat.credencial.probar') }}" class="mb-6">
                        @csrf
                        <x-secondary-button type="submit">Probar credencial actual</x-secondary-button>
                        <span class="ms-2 text-xs text-gray-500">Valida el NIT de la empresa contra la SAT.</span>
                    </form>

                    <form method="POST" action="{{ route('sat.credencial.update') }}"
                          class="border-t border-gray-100 pt-6">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-5">
                            <div>
                                <x-input-label for="nit" value="Usuario (NIT de la empresa)" />
                                <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full font-mono"
                                              value="{{ old('nit', $credential->nit) }}" required maxlength="20" />
                                <x-input-error :messages="$errors->get('nit')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password" value="Nueva contraseña SAT" />
                                <x-text-input id="password" name="password" type="password"
                                              class="mt-1 block w-full" required autocomplete="new-password" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Se guarda cifrada. El sistema anterior la mostraba en pantalla y la enviaba
                                    en el código de la página.
                                </p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                              class="mt-1 block w-full" required autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6">
                            <x-primary-button
                                onclick="return confirm('¿Actualizar la credencial SAT de {{ $credential->name }}?')">
                                Guardar credencial
                            </x-primary-button>
                            <span class="ms-2 text-xs text-gray-500">Se le pedirá confirmar su propia contraseña.</span>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
