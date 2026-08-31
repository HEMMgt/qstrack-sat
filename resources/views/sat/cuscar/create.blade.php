<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">SAT — Agregar cuscar</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-status-banner />
                <x-sat-alert />

                <p class="mb-2 text-sm text-gray-600">
                    Seleccione el archivo cuscar. Se revisará antes de transmitirlo a la SAT.
                </p>
                <p class="mb-6 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-600">
                    El nombre debe seguir el formato <code class="font-mono">TCCCNNNN.JJJ</code>:
                    tipo de servicio (P o E), correlativo de cuatro dígitos en los caracteres 5 al 8,
                    y fecha juliana de tres dígitos como extensión. Ejemplo:
                    <code class="font-mono">P0011234.123</code>. Tamaño máximo
                    {{ number_format(config('sat.cuscar.max_bytes')) }} bytes.
                </p>

                <form method="POST" action="{{ route('sat.cuscar.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <x-input-label for="archivo" value="Archivo cuscar" />
                        <input id="archivo" name="archivo" type="file" required
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-gray-700">
                        <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>Cargar archivo</x-primary-button>
                        <a href="{{ route('sat.cuscar.index') }}" class="text-sm text-gray-600 hover:underline">
                            Ver historial
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
