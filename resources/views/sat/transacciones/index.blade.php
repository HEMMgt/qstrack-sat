<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Transacciones con la SAT</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
                    <div>
                        <x-input-label for="endpoint" value="Operación" />
                        <select id="endpoint" name="endpoint"
                                class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todas</option>
                            @foreach ($endpoints as $endpoint)
                                <option value="{{ $endpoint->value }}"
                                    @selected(($filtros['endpoint'] ?? '') === $endpoint->value)>{{ $endpoint->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="resultado" value="Resultado" />
                        <select id="resultado" name="resultado"
                                class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="exito" @selected(($filtros['resultado'] ?? '') === 'exito')>Exitosas</option>
                            <option value="fallo" @selected(($filtros['resultado'] ?? '') === 'fallo')>Fallidas</option>
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
                    <a href="{{ route('sat.transacciones.index') }}" class="text-sm text-gray-600 hover:underline">Limpiar</a>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3">Fecha</th>
                                <th class="px-3 py-3">Operación</th>
                                <th class="px-3 py-3">Resultado</th>
                                <th class="px-3 py-3">HTTP</th>
                                <th class="px-3 py-3">Duración</th>
                                <th class="px-3 py-3">Usuario</th>
                                <th class="px-3 py-3">Descripción</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-3 text-gray-500">
                                        {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-3 py-3">{{ $transaction->endpoint->label() }}</td>
                                    <td class="px-3 py-3">
                                        @if ($transaction->succeeded)
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Éxito</span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-800">
                                                {{ $transaction->error_class ? 'Error' : 'Rechazado' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-gray-600">{{ $transaction->http_status ?? '—' }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $transaction->duration_ms }} ms</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $transaction->user?->name ?? '—' }}</td>
                                    <td class="max-w-md truncate px-3 py-3 text-gray-600">
                                        {{ $transaction->response_description ?? $transaction->error_message ?? '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('sat.transacciones.show', $transaction) }}"
                                           class="text-indigo-600 hover:underline">Detalle</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">Sin transacciones.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $transactions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
