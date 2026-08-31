<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Inicio</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-status-banner />

                <h3 class="text-lg font-semibold text-gray-900">
                    Hola, {{ auth()->user()->name }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Rol: {{ auth()->user()->role->label() }}
                    @if ($credential = auth()->user()->satCredential())
                        · Credencial SAT: {{ $credential->name }} ({{ $credential->nit }})
                    @else
                        · Sin credencial SAT asignada
                    @endif
                </p>
            </div>

            @canany(['sat.validar-nit', 'sat.validar-cuscar', 'sat.agregar-cuscar', 'sat.consultar-manifiesto'])
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @can('sat.validar-nit')
                        <a href="{{ route('sat.nit.create') }}"
                           class="block rounded-lg bg-white p-5 shadow transition hover:shadow-md">
                            <h4 class="font-semibold text-gray-900">Validar NIT</h4>
                            <p class="mt-1 text-sm text-gray-600">Comprueba un NIT ante la SAT.</p>
                        </a>
                    @endcan
                    @can('sat.validar-cuscar')
                        <a href="{{ route('sat.cuscar.validar.create') }}"
                           class="block rounded-lg bg-white p-5 shadow transition hover:shadow-md">
                            <h4 class="font-semibold text-gray-900">Validar cuscar</h4>
                            <p class="mt-1 text-sm text-gray-600">Revisa si un archivo tiene errores.</p>
                        </a>
                    @endcan
                    @can('sat.agregar-cuscar')
                        <a href="{{ route('sat.cuscar.create') }}"
                           class="block rounded-lg bg-white p-5 shadow transition hover:shadow-md">
                            <h4 class="font-semibold text-gray-900">Agregar cuscar</h4>
                            <p class="mt-1 text-sm text-gray-600">Carga y transmite un archivo.</p>
                        </a>
                    @endcan
                    @can('sat.consultar-manifiesto')
                        <a href="{{ route('sat.manifiesto.create') }}"
                           class="block rounded-lg bg-white p-5 shadow transition hover:shadow-md">
                            <h4 class="font-semibold text-gray-900">Consultar manifiesto</h4>
                            <p class="mt-1 text-sm text-gray-600">Consulta el encabezado de un manifiesto.</p>
                        </a>
                    @endcan
                </div>
            @endcanany
        </div>
    </div>
</x-app-layout>
