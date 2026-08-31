<?php

namespace App\Http\Controllers\Sat;

use App\Enums\CuscarStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sat\ValidarCuscarRequest;
use App\Models\CuscarFile;
use App\Services\Sat\Exceptions\SatException;
use App\Services\Sat\SatClientFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta a la SAT si un cuscar ya transmitido tiene errores.
 */
class ValidarCuscarController extends Controller
{
    public function __construct(private readonly SatClientFactory $clients) {}

    public function create(Request $request): View
    {
        return view('sat.cuscar.validar-create', [
            'nombreArchivo' => $request->string('nombreArchivo')->trim()->value(),
            'recienEnviado' => (bool) session('recien_enviado'),
            'esperaSegundos' => (int) config('sat.cuscar.validation_delay_seconds'),
        ]);
    }

    public function store(ValidarCuscarRequest $request): RedirectResponse
    {
        $nombre = $request->validated('nombreArchivo');
        $user = $request->user();

        try {
            $response = $this->clients->forUser($user)->consultarErroresCuscar($nombre);
        } catch (SatException $e) {
            return back()->withInput()->with('sat_error', $e->userMessage());
        }

        $file = CuscarFile::query()->ownedBy($user)->where('filename', $nombre)->latest()->first();

        if (! $response->isSuccess()) {
            $file?->update([
                'status' => CuscarStatus::Rechazado,
                'last_response_description' => $response->descripcion,
            ]);

            return back()->withInput()->with('sat_error', $response->descripcion);
        }

        if ($file !== null) {
            $file->update([
                'status' => CuscarStatus::Aceptado,
                'numero_manifiesto' => $response->manifiesto->numeroManifiesto ?? $file->numero_manifiesto,
                'fecha_recepcion' => $response->manifiesto->fechaRecepcion,
                'last_response_description' => $response->descripcion,
            ]);

            return redirect()
                ->route('sat.cuscar.validar.show', $file)
                ->with('status', $response->descripcion);
        }

        // Nombre sin registro local: el usuario consultó un cuscar que no subió
        // desde este sistema. Se muestra el resultado sin persistir un archivo
        // que no tenemos.
        return back()->with('sat_result', [
            'exito' => true,
            'descripcion' => $response->descripcion,
            'referencia' => $response->transactionUuid,
            'manifiesto' => $response->manifiesto->toArray(),
            'nombreArchivo' => $nombre,
        ]);
    }

    public function show(CuscarFile $cuscar): View
    {
        $this->authorize('view', $cuscar);

        return view('sat.cuscar.validar-show', ['file' => $cuscar]);
    }
}
