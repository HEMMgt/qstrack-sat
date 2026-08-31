<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">SAT — Validar NIT</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-status-banner />
                <x-sat-alert />

                <p class="mb-6 text-sm text-gray-600">
                    Consulta si un NIT está registrado y activo ante la SAT.
                </p>

                <form method="POST" action="{{ route('sat.nit.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="nit" value="NIT a validar" />
                        <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full font-mono"
                                      value="{{ old('nit') }}" required autofocus
                                      placeholder="Ej. 12345678" maxlength="20" />
                        <x-input-error :messages="$errors->get('nit')" class="mt-2" />
                    </div>

                    <div class="mt-6">
                        <x-primary-button>Validar NIT</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
