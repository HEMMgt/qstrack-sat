<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Usuarios</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg">
                <x-status-message />

                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex items-end gap-2">
                        <div>
                            <x-input-label for="buscar" value="Buscar" />
                            <x-text-input id="buscar" name="buscar" type="search" class="mt-1 block w-64"
                                          value="{{ $buscar }}" placeholder="Nombre o correo" />
                        </div>
                        <x-secondary-button type="submit">Buscar</x-secondary-button>
                    </form>

                    <a href="{{ route('admin.usuarios.create') }}"
                       class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                        Nuevo usuario
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Nombre</th>
                                <th class="px-4 py-3">Correo</th>
                                <th class="px-4 py-3">Rol</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Último ingreso</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3">{{ $user->role->label() }}</td>
                                    <td class="px-4 py-3">
                                        @if ($user->is_active)
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Activo</span>
                                        @else
                                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.usuarios.edit', $user) }}"
                                           class="text-indigo-600 hover:underline">Editar</a>
                                        @if ($user->is_active && ! $user->is(auth()->user()))
                                            <form method="POST" action="{{ route('admin.usuarios.destroy', $user) }}"
                                                  class="ml-3 inline"
                                                  onsubmit="return confirm('¿Desactivar a {{ $user->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Desactivar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Sin usuarios.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
