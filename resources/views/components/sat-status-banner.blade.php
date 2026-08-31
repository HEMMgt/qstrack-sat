{{-- Aviso de disponibilidad. Nunca bloquea la pantalla: si la SAT está caída
     el formulario sigue usable y el intento quedará registrado. --}}
@unless ($satIsUp)
    <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <strong>El servicio web de la SAT no responde.</strong>
        Puede intentar de todas formas, pero es probable que la operación falle.
    </div>
@endunless

@if ($satEnvironment !== 'produccion')
    <div class="mb-4 rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-900">
        Ambiente de <strong>{{ $satEnvironment }}</strong> — {{ $satBaseUrl }}
    </div>
@endif
