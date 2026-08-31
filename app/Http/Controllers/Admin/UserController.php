<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\AuditLogger;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->string('buscar')->trim()->value(), function ($query, string $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios.index', [
            'users' => $users,
            'buscar' => $request->string('buscar')->trim()->value(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.create', ['roles' => Role::options()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        AuditLogger::log('usuario.creado', $user, "Se creó el usuario {$user->email}", [
            'role' => $user->role->value,
        ]);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', "Usuario {$user->name} creado.");
    }

    public function edit(User $user): View
    {
        return view('admin.usuarios.edit', [
            'user' => $user,
            'roles' => Role::options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $anterior = ['role' => $user->role->value, 'is_active' => $user->is_active];

        $user->update($data);

        AuditLogger::log('usuario.actualizado', $user, "Se actualizó el usuario {$user->email}", [
            'old' => $anterior,
            'new' => ['role' => $user->role->value, 'is_active' => $user->is_active],
            'password_cambiada' => array_key_exists('password', $data),
        ]);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', "Usuario {$user->name} actualizado.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'No puede desactivar su propia cuenta.']);
        }

        // Desactivar en vez de borrar: los cuscar y transacciones referencian al
        // usuario y queremos conservar la trazabilidad.
        $user->update(['is_active' => false]);

        AuditLogger::log('usuario.desactivado', $user, "Se desactivó el usuario {$user->email}");

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', "Usuario {$user->name} desactivado.");
    }
}
