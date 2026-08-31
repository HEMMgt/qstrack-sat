@canany(['usuarios.manage', 'credenciales.manage', 'bitacora.view'])
    <div class="hidden sm:flex sm:items-center">
        <x-dropdown align="left" width="56">
            <x-slot name="trigger">
                <button class="inline-flex h-16 items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                    Administración
                    <svg class="ms-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                @can('usuarios.manage')
                    <x-dropdown-link :href="route('admin.usuarios.index')">Usuarios</x-dropdown-link>
                @endcan
                @can('credenciales.manage')
                    <x-dropdown-link :href="route('admin.credenciales.index')">Credenciales SAT</x-dropdown-link>
                @endcan
                @can('bitacora.view')
                    <x-dropdown-link :href="route('admin.bitacora.index')">Bitácora</x-dropdown-link>
                @endcan
            </x-slot>
        </x-dropdown>
    </div>
@endcanany
