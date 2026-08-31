@php($manifiesto = $manifest->toManifiesto())

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Manifiesto {{ $manifest->numero_manifiesto ?? $manifest->numero_manifiesto_consultado }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-sat-alert />

                <dl class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    @foreach ($manifiesto->toArray() as $campo => $valor)
                        <div @class(['sm:col-span-2' => $campo === 'firmaElectronica'])>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">
                                {{ \App\Services\Sat\DTO\Manifiesto::labels()[$campo] }}
                            </dt>
                            <dd @class([
                                'mt-0.5 text-gray-900',
                                'break-all font-mono text-xs' => $campo === 'firmaElectronica',
                            ])>{{ $valor ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-6 border-t border-gray-100 pt-4 text-xs text-gray-500">
                    Consultado el {{ $manifest->queried_at->format('d/m/Y H:i:s') }}
                    @if ($manifest->transaction)
                        · referencia {{ $manifest->transaction->uuid }}
                    @endif
                </div>

                <div class="mt-6 flex items-center gap-4">
                    <a href="{{ route('sat.manifiesto.create') }}"
                       class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                        Consultar otro
                    </a>
                    <a href="{{ route('sat.manifiesto.index') }}" class="text-sm text-gray-600 hover:underline">
                        Ver historial
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
