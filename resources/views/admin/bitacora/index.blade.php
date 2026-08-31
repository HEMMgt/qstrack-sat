<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Bitácora</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
                    <div>
                        <x-input-label for="accion" value="Acción" />
                        <select id="accion" name="accion"
                                class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todas</option>
                            @foreach ($acciones as $accion)
                                <option value="{{ $accion }}" @selected(($filtros['accion'] ?? '') === $accion)>
                                    {{ $accion }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="usuario" value="Usuario" />
                        <select id="usuario" name="usuario"
                                class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            @foreach ($usuarios as $usuario)
                                <option value="{{ $usuario->id }}"
                                    @selected((int) ($filtros['usuario'] ?? 0) === $usuario->id)>{{ $usuario->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="desde" value="Desde" />
                        <x-text-input id="desde" name="desde" type="date" class="mt-1 text-sm"
                                      value="{{ $filtros['desde'] ?? '' }}" />
                    </div>
                    <div>
                        <x-input-label for="hasta" value="Hasta" />
                        <x-text-input id="hasta" name="hasta" type="date" class="mt-1 text-sm"
                                      value="{{ $filtros['hasta'] ?? '' }}" />
                    </div>
                    <x-secondary-button type="submit">Filtrar</x-secondary-button>
                    <a href="{{ route('admin.bitacora.index') }}" class="text-sm text-gray-600 hover:underline">Limpiar</a>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3">Fecha</th>
                                <th class="px-3 py-3">Acción</th>
                                <th class="px-3 py-3">Usuario</th>
                                <th class="px-3 py-3">Detalle</th>
                                <th class="px-3 py-3">Origen</th>
                                <th class="px-3 py-3">Datos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-3 text-gray-500">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-3 py-3 font-mono text-xs">{{ $log->action }}</td>
                                    <td class="px-3 py-3">{{ $log->user?->name ?? '—' }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $log->description ?? '—' }}</td>
                                    <td class="px-3 py-3 font-mono text-xs text-gray-500">{{ $log->ip_address ?? '—' }}</td>
                                    <td class="px-3 py-3">
                                        @if ($log->properties)
                                            <details>
                                                <summary class="cursor-pointer text-xs text-indigo-600">Ver</summary>
                                                <pre class="mt-2 max-w-md overflow-auto rounded bg-gray-50 p-2 text-xs">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </details>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Sin registros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $logs->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
