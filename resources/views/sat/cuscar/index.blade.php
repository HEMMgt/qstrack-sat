<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Archivos cuscar</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-alert />

                <div class="mb-4 flex justify-end">
                    <a href="{{ route('sat.cuscar.create') }}"
                       class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                        Cargar cuscar
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Archivo</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Manifiesto</th>
                                <th class="px-4 py-3">Tamaño</th>
                                <th class="px-4 py-3">Cargado</th>
                                <th class="px-4 py-3">Transmitido</th>
                                <th class="px-4 py-3">Por</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($files as $file)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-gray-900">{{ $file->filename }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs {{ $file->status->color() }}">
                                            {{ $file->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono">{{ $file->numero_manifiesto ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ number_format($file->size_bytes) }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $file->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $file->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $file->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('sat.cuscar.show', $file) }}"
                                           class="text-indigo-600 hover:underline">Ver</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                        Todavía no se ha cargado ningún archivo cuscar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $files->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
