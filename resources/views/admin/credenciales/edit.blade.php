<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar credencial SAT</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('admin.credenciales.update', $credential) }}">
                    @csrf
                    @method('PATCH')
                    @include('admin.credenciales._form', ['credential' => $credential])

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>Guardar cambios</x-primary-button>
                        <a href="{{ route('admin.credenciales.show', $credential) }}"
                           class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">Eliminar credencial</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Se retira del sistema y deja de estar disponible para los usuarios asignados.
                    El historial de transacciones se conserva.
                </p>
                <form method="POST" action="{{ route('admin.credenciales.destroy', $credential) }}" class="mt-4"
                      onsubmit="return confirm('¿Eliminar la credencial {{ $credential->name }}?')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Eliminar</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
