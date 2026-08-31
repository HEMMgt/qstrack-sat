<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">SAT — Validar cuscar</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-status-banner />
                <x-sat-alert />

                @if ($recienEnviado)
                    <div class="mb-4 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                        <strong>Espere {{ intdiv($esperaSegundos, 60) }} minutos antes de validar.</strong>
                        La SAT no procesa el archivo de inmediato; si consulta antes, reportará que no lo encuentra.
                    </div>
                @endif

                <p class="mb-6 text-sm text-gray-600">
                    Consulta si un cuscar ya transmitido fue procesado correctamente o tiene errores.
                </p>

                <form method="POST" action="{{ route('sat.cuscar.validar.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="nombreArchivo" value="Nombre del archivo cuscar" />
                        <x-text-input id="nombreArchivo" name="nombreArchivo" type="text"
                                      class="mt-1 block w-full font-mono"
                                      value="{{ old('nombreArchivo', $nombreArchivo) }}"
                                      required autofocus maxlength="12" placeholder="P0011234.123" />
                        <x-input-error :messages="$errors->get('nombreArchivo')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>Validar cuscar</x-primary-button>
                        <a href="{{ route('sat.cuscar.index') }}" class="text-sm text-gray-600 hover:underline">
                            Ver historial
                        </a>
                    </div>
                </form>

                @if ($result = session('sat_result'))
                    @if (! empty($result['manifiesto']))
                        <dl class="mt-6 grid gap-4 border-t border-gray-100 pt-6 sm:grid-cols-2">
                            @foreach (['nombreCuscar', 'numeroManifiesto', 'fechaRecepcion'] as $campo)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-gray-500">
                                        {{ \App\Services\Sat\DTO\Manifiesto::labels()[$campo] }}
                                    </dt>
                                    <dd class="text-gray-900">{{ $result['manifiesto'][$campo] ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
