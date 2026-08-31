<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Cuscar validado: {{ $file->filename }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-alert />

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Nombre del cuscar</dt>
                        <dd class="font-mono text-gray-900">{{ $file->filename }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Número de manifiesto</dt>
                        <dd class="font-mono text-gray-900">{{ $file->numero_manifiesto ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Fecha de recepción</dt>
                        <dd class="text-gray-900">{{ $file->fecha_recepcion ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Estado</dt>
                        <dd>
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $file->status->color() }}">
                                {{ $file->status->label() }}
                            </span>
                        </dd>
                    </div>
                    @if ($file->last_response_description)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Respuesta de la SAT</dt>
                            <dd class="text-gray-900">{{ $file->last_response_description }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 flex items-center gap-4">
                    <a href="{{ route('sat.cuscar.validar.create') }}"
                       class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                        Validar otro
                    </a>
                    <a href="{{ route('sat.cuscar.show', $file) }}" class="text-sm text-gray-600 hover:underline">
                        Ver el archivo
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
