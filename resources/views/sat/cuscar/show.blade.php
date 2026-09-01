<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Cuscar {{ $file->filename }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-status-banner />
                <x-sat-alert />

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Nombre</dt>
                        <dd class="font-mono text-gray-900">{{ $file->filename }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Estado</dt>
                        <dd>
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $file->status->color() }}">
                                {{ $file->status->label() }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Tamaño</dt>
                        <dd class="text-gray-900">{{ number_format($file->size_bytes) }} bytes</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Tipo / correlativo / juliana</dt>
                        <dd class="font-mono text-gray-900">
                            {{ $file->service_type }} · {{ $file->correlativo }} · {{ $file->julian_extension }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-gray-500">SHA-256</dt>
                        <dd class="break-all font-mono text-xs text-gray-700">{{ $file->sha256 }}</dd>
                    </div>
                    @if ($file->numero_manifiesto)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Número de manifiesto</dt>
                            <dd class="font-mono text-gray-900">{{ $file->numero_manifiesto }}</dd>
                        </div>
                    @endif
                    @if ($file->sent_at)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Transmitido</dt>
                            <dd class="text-gray-900">{{ $file->sent_at->format('d/m/Y H:i:s') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="mb-4 text-base font-semibold text-gray-900">Cabecera del archivo</h3>

                @php($coincide = $credencial?->admiteEmisor($file->emisor) ?? true)

                @unless ($coincide)
                    {{-- Enviarlo así provoca un rechazo en el segmento UNB que no
                         explica cuál es el problema real. --}}
                    <div class="mb-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <strong>El emisor del archivo no corresponde a su credencial.</strong>
                        La SAT rechazaría este manifiesto. Solicite a un administrador la
                        credencial de la empresa emisora.
                    </div>
                @endunless

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Emisor declarado en el archivo</dt>
                        <dd @class([
                            'font-mono',
                            'text-gray-900' => $coincide,
                            'font-semibold text-red-700' => ! $coincide,
                        ])>{{ $file->emisor ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Se transmitirá como</dt>
                        <dd class="text-gray-900">
                            {{ $credencial?->label() ?? 'Sin credencial asignada' }}
                            @if ($credencial?->gln)
                                <span class="block font-mono text-xs text-gray-500">Emisor: {{ $credencial->gln }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Número de manifiesto declarado</dt>
                        <dd class="font-mono text-gray-900">{{ $file->numero_manifiesto_declarado ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="mb-3 text-base font-semibold text-gray-900">Contenido</h3>

                @if ($missing)
                    <p class="text-sm text-red-700">El archivo ya no está disponible en el servidor.</p>
                @else
                    {{-- Solo lectura: se transmite el archivo guardado, no lo que
                         se vea aquí. El sistema legacy lo ponía en un textarea
                         editable de un megabyte. --}}
                    <pre class="max-h-96 overflow-auto rounded-md bg-gray-900 p-4 font-mono text-xs leading-relaxed text-gray-100">{{ implode("\n", $preview['lines']) }}</pre>

                    @if ($preview['truncated'])
                        <p class="mt-2 text-xs text-gray-500">
                            Mostrando las primeras {{ number_format(count($preview['lines'])) }} de
                            {{ number_format($preview['total']) }} líneas.
                        </p>
                    @endif

                    <a href="{{ route('sat.cuscar.download', $file) }}"
                       class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Descargar original</a>
                @endif
            </div>

            @can('send', $file)
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900">Transmitir a la SAT</h3>

                    <form method="POST" action="{{ route('sat.cuscar.send', $file) }}" class="mt-4"
                          onsubmit="return confirm('¿Transmitir {{ $file->filename }} a la SAT?')">
                        @csrf

                        @if ($file->wasSent())
                            <label class="mb-4 flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                <input type="checkbox" name="reenviar" value="1" class="mt-0.5 rounded border-gray-300">
                                <span>
                                    Este archivo ya fue transmitido. Confirmo que deseo reenviarlo,
                                    aun sabiendo que la SAT podría registrar un manifiesto duplicado.
                                </span>
                            </label>
                        @endif

                        <x-primary-button :disabled="$missing || ! $coincide">Transmitir cuscar</x-primary-button>
                        <a href="{{ route('sat.cuscar.create') }}" class="ms-3 text-sm text-gray-600 hover:underline">
                            Cambiar archivo
                        </a>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
