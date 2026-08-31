@canany(['sat.validar-nit', 'sat.validar-cuscar', 'sat.agregar-cuscar', 'sat.consultar-manifiesto', 'sat.cambiar-clave', 'transacciones.view'])
    <div class="hidden sm:flex sm:items-center">
        <x-dropdown align="left" width="56">
            <x-slot name="trigger">
                <button @class([
                    'inline-flex h-16 items-center border-b-2 px-1 pt-1 text-sm font-medium',
                    'border-indigo-400 text-gray-900' => request()->routeIs('sat.*'),
                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => ! request()->routeIs('sat.*'),
                ])>
                    SAT
                    <svg class="ms-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                @can('sat.validar-nit')
                    <x-dropdown-link :href="route('sat.nit.create')">Validar NIT</x-dropdown-link>
                @endcan
                @can('sat.validar-cuscar')
                    <x-dropdown-link :href="route('sat.cuscar.validar.create')">Validar cuscar</x-dropdown-link>
                @endcan
                @can('sat.agregar-cuscar')
                    <x-dropdown-link :href="route('sat.cuscar.create')">Agregar cuscar</x-dropdown-link>
                @endcan
                @can('sat.consultar-manifiesto')
                    <x-dropdown-link :href="route('sat.manifiesto.create')">Consultar manifiesto</x-dropdown-link>
                    <x-dropdown-link :href="route('sat.manifiesto.index')">Manifiestos consultados</x-dropdown-link>
                @endcan
                @can('sat.agregar-cuscar')
                    <x-dropdown-link :href="route('sat.cuscar.index')">Archivos cuscar</x-dropdown-link>
                @endcan
                @can('sat.cambiar-clave')
                    <x-dropdown-link :href="route('sat.credencial.edit')">Cambiar clave SAT</x-dropdown-link>
                @endcan
                @can('viewAny', App\Models\SatTransaction::class)
                    <x-dropdown-link :href="route('sat.transacciones.index')">Transacciones</x-dropdown-link>
                @endcan
            </x-slot>
        </x-dropdown>
    </div>
@endcanany
