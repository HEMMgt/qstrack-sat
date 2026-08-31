<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Credenciales SAT</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-status-message />

                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" action="{{ route('admin.credenciales.index') }}" class="flex items-end gap-2">
                        <div>
                            <x-input-label for="buscar" value="Buscar" />
                            <x-text-input id="buscar" name="buscar" type="search" class="mt-1 block w-64"
                                          value="{{ $buscar }}" placeholder="Empresa o NIT" />
                        </div>
                        <x-secondary-button type="submit">Buscar</x-secondary-button>
                    </form>

                    <a href="{{ route('admin.credenciales.create') }}"
                       class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                        Nueva credencial
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Empresa</th>
                                <th class="px-4 py-3">NIT</th>
                                <th class="px-4 py-3">Contraseña</th>
                                <th class="px-4 py-3">Ambiente</th>
                                <th class="px-4 py-3">Usuarios</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($credentials as $credential)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $credential->name }}</td>
                                    <td class="px-4 py-3 font-mono">{{ $credential->nit }}</td>
                                    {{-- Nunca se muestra: el sistema anterior la listaba en claro. --}}
                                    <td class="px-4 py-3 text-gray-400">••••••••</td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-xs',
                                            'bg-amber-100 text-amber-800' => $credential->environment === 'pruebas',
                                            'bg-red-100 text-red-800' => $credential->environment === 'produccion',
                                        ])>{{ ucfirst($credential->environment) }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ $credential->users_count }}</td>
                                    <td class="px-4 py-3">
                                        @if ($credential->is_active)
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Activa</span>
                                        @else
                                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.credenciales.show', $credential) }}"
                                           class="text-indigo-600 hover:underline">Ver</a>
                                        <a href="{{ route('admin.credenciales.edit', $credential) }}"
                                           class="ms-3 text-indigo-600 hover:underline">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">Sin credenciales registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $credentials->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
