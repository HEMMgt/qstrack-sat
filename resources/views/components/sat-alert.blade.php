{{-- Resultado de una operación con la SAT. Todo se escapa: el sistema legacy
     imprimía estos textos desde la URL sin escapar. --}}
@if (session('sat_error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('sat_error') }}
    </div>
@endif

@if (session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('status') }}
    </div>
@endif

@if ($result = session('sat_result'))
    <div @class([
        'mb-4 rounded-md border px-4 py-3 text-sm',
        'border-green-200 bg-green-50 text-green-800' => $result['exito'],
        'border-red-200 bg-red-50 text-red-800' => ! $result['exito'],
    ])>
        <p class="font-semibold">{{ $result['exito'] ? 'Operación exitosa' : 'La SAT rechazó la operación' }}</p>
        <p class="mt-1">{{ $result['descripcion'] }}</p>
        @if (! empty($result['referencia']))
            <p class="mt-2 text-xs opacity-75">Referencia: {{ $result['referencia'] }}</p>
        @endif
    </div>
@endif
