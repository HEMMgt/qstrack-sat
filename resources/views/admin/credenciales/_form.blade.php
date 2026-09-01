@props(['credential' => null])

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Empresa" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      value="{{ old('name', $credential?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nit" value="NIT de la empresa transmisora" />
        <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full font-mono"
                      value="{{ old('nit', $credential?->nit) }}" required />
        <p class="mt-1 text-xs text-gray-500">Es el parámetro <code>usuario</code> que se envía a la SAT.</p>
        <x-input-error :messages="$errors->get('nit')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="gln" value="Código de emisor (GLN)" />
        <x-text-input id="gln" name="gln" type="text" class="mt-1 block w-full font-mono"
                      value="{{ old('gln', $credential?->gln) }}" maxlength="35" />
        <p class="mt-1 text-xs text-gray-500">
            Opcional. Es el código que la SAT muestra como «Emisor (GLN)» en sus manifiestos
            y el que va en el segmento <code>UNB</code> de los cuscar de esta empresa.
            Si lo captura, el sistema impedirá transmitir archivos de otro emisor con esta credencial.
        </p>
        <x-input-error :messages="$errors->get('gln')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password"
                       :value="$credential ? 'Nueva contraseña SAT (vacío = sin cambio)' : 'Contraseña SAT'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                      autocomplete="new-password" :required="! $credential" />
        <p class="mt-1 text-xs text-gray-500">Se guarda cifrada y no vuelve a mostrarse.</p>
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="environment" value="Ambiente" />
        <select id="environment" name="environment" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (['pruebas' => 'Pruebas (prefarm3)', 'produccion' => 'Producción (farm3)'] as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('environment', $credential?->environment ?? 'pruebas') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('environment')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notas (opcional)" />
        <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full"
                      value="{{ old('notes', $credential?->notes) }}" />
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div>
        <label class="flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                   @checked(old('is_active', $credential?->is_active ?? true))>
            <span class="ms-2 text-sm text-gray-700">Credencial activa</span>
        </label>
    </div>
</div>
