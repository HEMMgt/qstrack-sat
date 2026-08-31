<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sat\UpdateMySatCredentialRequest;
use App\Services\Sat\Exceptions\SatException;
use App\Services\Sat\SatClientFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\AuditLogger;

class MiCredencialSatController extends Controller
{
    public function __construct(private readonly SatClientFactory $clients) {}

    public function edit(Request $request): View
    {
        return view('sat.credencial.edit', [
            'credential' => $request->user()->satCredential(),
        ]);
    }

    public function update(UpdateMySatCredentialRequest $request): RedirectResponse
    {
        // La credencial sale del usuario en sesión. El formulario no lleva ningún
        // identificador que se pueda manipular.
        $credential = $request->user()->satCredential();

        abort_if($credential === null, 404);

        $this->authorize('rotateSecret', $credential);

        $nitAnterior = $credential->nit;

        $credential->update($request->validated() + ['secret_rotated_at' => now()]);

        AuditLogger::log(
            'credencial.secreto_rotado',
            $credential,
            "Se actualizó la credencial SAT de {$credential->name}",
            // Solo el NIT: la contraseña no se registra ni cifrada.
            ['nit' => ['old' => $nitAnterior, 'new' => $credential->nit]],
        );

        return redirect()
            ->route('sat.credencial.edit')
            ->with('status', 'Credencial SAT actualizada.');
    }

    /**
     * Comprueba contra la SAT que la credencial guardada funciona, validando su
     * propio NIT. Evita descubrir que la clave está mal al enviar un cuscar.
     */
    public function probar(Request $request): RedirectResponse
    {
        $credential = $request->user()->satCredential();

        abort_if($credential === null, 404);

        $this->authorize('rotateSecret', $credential);

        try {
            $response = $this->clients->forUser($request->user())->validarNit($credential->nit);
        } catch (SatException $e) {
            return back()->with('sat_error', $e->userMessage());
        }

        return back()->with('sat_result', [
            'exito' => $response->isSuccess(),
            'descripcion' => $response->descripcion,
            'referencia' => $response->transactionUuid,
        ]);
    }
}
