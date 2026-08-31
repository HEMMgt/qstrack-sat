<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $credential->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <x-status-message />

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">NIT</dt>
                        <dd class="font-mono text-gray-900">{{ $credential->nit }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Contraseña</dt>
                        <dd class="text-gray-400">•••••••• <span class="text-xs">(cifrada, no se muestra)</span></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Ambiente</dt>
                        <dd class="text-gray-900">{{ ucfirst($credential->environment) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Estado</dt>
                        <dd class="text-gray-900">{{ $credential->is_active ? 'Activa' : 'Inactiva' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Última rotación de clave</dt>
                        <dd class="text-gray-900">{{ $credential->secret_rotated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Creada por</dt>
                        <dd class="text-gray-900">{{ $credential->creator?->name ?? '—' }}</dd>
                    </div>
                    @if ($credential->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Notas</dt>
                            <dd class="text-gray-900">{{ $credential->notes }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6">
                    <a href="{{ route('admin.credenciales.edit', $credential) }}"
                       class="text-sm text-indigo-600 hover:underline">Editar credencial</a>
                </div>
            </div>

            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">Usuarios asignados</h3>

                <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($credential->users as $user)
                            <tr>
                                <td class="py-3 font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="py-3 text-gray-500">
                                    Desde {{ \Illuminate\Support\Carbon::parse($user->pivot->assigned_at)->format('d/m/Y') }}
                                </td>
                                <td class="py-3 text-right">
                                    <form method="POST" action="{{ route('admin.asignaciones.destroy', $user) }}"
                                          onsubmit="return confirm('¿Retirar la credencial de {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Retirar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-4 text-gray-500">Ningún usuario tiene esta credencial.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <form method="POST" action="{{ route('admin.credenciales.asignar', $credential) }}"
                      class="mt-6 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-6">
                    @csrf
                    <div>
                        <x-input-label for="user_id" value="Asignar a" />
                        <select id="user_id" name="user_id" required
                                class="mt-1 block w-72 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Seleccione un usuario —</option>
                            @foreach ($assignable as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                    </div>
                    <x-primary-button>Asignar</x-primary-button>
                    <p class="w-full text-xs text-gray-500">
                        Solo aparecen usuarios activos que todavía no tienen credencial: cada usuario opera con una sola.
                    </p>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
