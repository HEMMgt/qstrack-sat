<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Manifiestos consultados</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-alert />

                <form method="GET" action="{{ route('sat.manifiesto.index') }}" class="mb-4 flex items-end gap-2">
                    <div>
                        <x-input-label for="buscar" value="Buscar" />
                        <x-text-input id="buscar" name="buscar" type="search" class="mt-1 block w-64"
                                      value="{{ $buscar }}" placeholder="Número de manifiesto" />
                    </div>
                    <x-secondary-button type="submit">Buscar</x-secondary-button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Manifiesto</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Dictamen</th>
                                <th class="px-4 py-3">Empresa transmisora</th>
                                <th class="px-4 py-3">Consultado</th>
                                <th class="px-4 py-3">Por</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($manifests as $manifest)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-gray-900">
                                        {{ $manifest->numero_manifiesto ?? $manifest->numero_manifiesto_consultado }}
                                    </td>
                                    <td class="px-4 py-3">{{ $manifest->estado ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $manifest->estado_dictamen ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $manifest->empresa_transmisora ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $manifest->queried_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $manifest->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('sat.manifiesto.show', $manifest) }}"
                                           class="text-indigo-600 hover:underline">Ver</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                        Todavía no se ha consultado ningún manifiesto.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $manifests->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
