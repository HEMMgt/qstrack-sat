<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sat\ValidarNitRequest;
use App\Services\Sat\Exceptions\SatException;
use App\Services\Sat\SatClientFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Support\AuditLogger;

class ValidarNitController extends Controller
{
    public function __construct(private readonly SatClientFactory $clients) {}

    public function create(): View
    {
        return view('sat.nit.create');
    }

    public function store(ValidarNitRequest $request): RedirectResponse
    {
        $nit = $request->validated('nit');

        try {
            $response = $this->clients->forUser($request->user())->validarNit($nit);
        } catch (SatException $e) {
            return back()->withInput()->with('sat_error', $e->userMessage());
        }

        AuditLogger::log('sat.nit.validado', null, "Se validó el NIT {$nit}", [
            'nit' => $nit,
            'resultado' => $response->tipo,
            'referencia' => $response->transactionUuid,
        ]);

        // Redirección tras el POST: refrescar la página no vuelve a consultar
        // a la SAT.
        return back()->with('sat_result', [
            'exito' => $response->isSuccess(),
            'descripcion' => $response->descripcion,
            'nit' => $nit,
            'referencia' => $response->transactionUuid,
        ]);
    }
}
