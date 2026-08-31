<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSatCredentialRequest;
use App\Http\Requests\Admin\UpdateSatCredentialRequest;
use App\Models\SatCredential;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\AuditLogger;

class SatCredentialController extends Controller
{
    public function index(Request $request): View
    {
        $credentials = SatCredential::query()
            ->withCount('users')
            ->when($request->string('buscar')->trim()->value(), function ($query, string $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('nit', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.credenciales.index', [
            'credentials' => $credentials,
            'buscar' => $request->string('buscar')->trim()->value(),
        ]);
    }

    public function create(): View
    {
        return view('admin.credenciales.create');
    }

    public function store(StoreSatCredentialRequest $request): RedirectResponse
    {
        $credential = SatCredential::create(
            $request->validated() + ['created_by' => $request->user()->id],
        );

        AuditLogger::log('credencial.creada', $credential, "Se creó la credencial {$credential->name}", [
            'nit' => $credential->nit,
            'environment' => $credential->environment,
        ]);

        return redirect()
            ->route('admin.credenciales.show', $credential)
            ->with('status', "Credencial {$credential->name} creada.");
    }

    public function show(SatCredential $credential): View
    {
        $credential->load('users', 'creator');

        return view('admin.credenciales.show', [
            'credential' => $credential,
            // Solo usuarios sin credencial: el pivote tiene unique(user_id).
            'assignable' => User::query()
                ->where('is_active', true)
                ->whereDoesntHave('satCredentials')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function edit(SatCredential $credential): View
    {
        return view('admin.credenciales.edit', ['credential' => $credential]);
    }

    public function update(UpdateSatCredentialRequest $request, SatCredential $credential): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['secret_rotated_at'] = now();
        }

        $anterior = ['nit' => $credential->nit, 'is_active' => $credential->is_active];

        $credential->update($data);

        AuditLogger::log('credencial.actualizada', $credential, "Se actualizó la credencial {$credential->name}", [
            'old' => $anterior,
            'new' => ['nit' => $credential->nit, 'is_active' => $credential->is_active],
            // Solo si cambió, nunca el valor.
            'secreto_rotado' => array_key_exists('secret_rotated_at', $data),
        ]);

        return redirect()
            ->route('admin.credenciales.show', $credential)
            ->with('status', 'Credencial actualizada.');
    }

    public function destroy(SatCredential $credential): RedirectResponse
    {
        $credential->delete();

        AuditLogger::log('credencial.eliminada', $credential, "Se eliminó la credencial {$credential->name}");

        return redirect()
            ->route('admin.credenciales.index')
            ->with('status', "Credencial {$credential->name} eliminada.");
    }
}
