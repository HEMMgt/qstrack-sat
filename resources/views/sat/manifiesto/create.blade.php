<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">SAT — Consultar manifiesto</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-status-banner />
                <x-sat-alert />

                <p class="mb-6 text-sm text-gray-600">
                    Consulta el encabezado de un manifiesto ya transmitido a la SAT.
                </p>

                <form method="POST" action="{{ route('sat.manifiesto.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="numeroManifiesto" value="Número de manifiesto" />
                        <x-text-input id="numeroManifiesto" name="numeroManifiesto" type="text"
                                      class="mt-1 block w-full font-mono"
                                      value="{{ old('numeroManifiesto') }}" required autofocus maxlength="60" />
                        <x-input-error :messages="$errors->get('numeroManifiesto')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>Consultar</x-primary-button>
                        <a href="{{ route('sat.manifiesto.index') }}" class="text-sm text-gray-600 hover:underline">
                            Ver consultas anteriores
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
