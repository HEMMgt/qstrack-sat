<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Nuevo usuario</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('admin.usuarios.store') }}">
                    @csrf
                    @include('admin.usuarios._form', ['user' => null, 'roles' => $roles])

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>Crear usuario</x-primary-button>
                        <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
