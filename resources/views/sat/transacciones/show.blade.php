<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $transaction->endpoint->label() }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Referencia</dt>
                        <dd class="font-mono text-xs text-gray-900">{{ $transaction->uuid }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Fecha</dt>
                        <dd class="text-gray-900">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Resultado</dt>
                        <dd class="text-gray-900">
                            {{ $transaction->succeeded ? 'Éxito' : 'Fallido' }}
                            @if ($transaction->response_type)
                                ({{ $transaction->response_type }})
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">HTTP / intentos / duración</dt>
                        <dd class="text-gray-900">
                            {{ $transaction->http_status ?? '—' }} ·
                            {{ $transaction->attempts }} ·
                            {{ $transaction->duration_ms }} ms
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Usuario</dt>
                        <dd class="text-gray-900">{{ $transaction->user?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Origen</dt>
                        <dd class="text-gray-900">{{ $transaction->ip_address ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Ambiente</dt>
                        <dd class="break-all text-xs text-gray-700">
                            {{ $transaction->environment }} — {{ $transaction->base_url }}
                        </dd>
                    </div>
                    @if ($transaction->response_description)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Respuesta de la SAT</dt>
                            <dd class="text-gray-900">{{ $transaction->response_description }}</dd>
                        </div>
                    @endif
                    @if ($transaction->error_message)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Error</dt>
                            <dd class="text-red-800">
                                {{ $transaction->error_class }}: {{ $transaction->error_message }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="mb-2 text-base font-semibold text-gray-900">Datos enviados</h3>
                <p class="mb-3 text-xs text-gray-500">
                    La contraseña se sustituye por asteriscos antes de guardarse.
                </p>
                <pre class="overflow-auto rounded-md bg-gray-900 p-4 font-mono text-xs text-gray-100">{{ json_encode($transaction->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>

            @if ($transaction->response_raw)
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="mb-3 text-base font-semibold text-gray-900">Respuesta sin procesar</h3>
                    <pre class="max-h-96 overflow-auto rounded-md bg-gray-900 p-4 font-mono text-xs text-gray-100">{{ $transaction->response_raw }}</pre>
                </div>
            @endif

            <a href="{{ route('sat.transacciones.index') }}" class="inline-block text-sm text-gray-600 hover:underline">
                Volver al historial
            </a>
        </div>
    </div>
</x-app-layout>
