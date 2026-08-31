<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SatCredential;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\AuditLogger;

class CredentialAssignmentController extends Controller
{
    public function store(Request $request, SatCredential $credential): RedirectResponse
    {
        $this->authorize('update', $credential);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
                // El pivote tiene unique(user_id): rechazamos aquí para dar un
                // mensaje claro en vez de un error de integridad.
                Rule::unique('sat_credential_user', 'user_id'),
            ],
        ], [
            'user_id.unique' => 'Ese usuario ya tiene una credencial SAT asignada.',
        ]);

        $credential->users()->attach($validated['user_id'], [
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        $user = User::find($validated['user_id']);

        AuditLogger::log(
            'credencial.asignada',
            $credential,
            "Se asignó la credencial {$credential->name} a {$user->email}",
            ['user_id' => $user->id],
        );

        return back()->with('status', "Credencial asignada a {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $credential = $user->satCredential();

        abort_if($credential === null, 404);

        $this->authorize('update', $credential);

        $credential->users()->detach($user->id);

        AuditLogger::log(
            'credencial.desasignada',
            $credential,
            "Se retiró la credencial {$credential->name} de {$user->email}",
            ['user_id' => $user->id],
        );

        return back()->with('status', "Se retiró la credencial de {$user->name}.");
    }
}
