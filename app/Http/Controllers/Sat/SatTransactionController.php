<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatTransaction;
use App\Models\User;
use App\Services\Sat\SatEndpoint;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Historial de llamadas a la SAT. Es la evidencia de qué se envió y qué
 * contestaron, incluido el cuerpo crudo cuando la respuesta vino mal formada.
 */
class SatTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SatTransaction::class);

        $puedeVerTodo = $request->user()->can('transacciones.view');

        $transactions = SatTransaction::query()
            ->with('user')
            ->unless($puedeVerTodo, fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($request->string('endpoint')->trim()->value(), fn ($q, string $e) => $q->where('endpoint', $e))
            ->when($request->string('resultado')->value(), function ($q, string $r) {
                $q->where('succeeded', $r === 'exito');
            })
            ->when($request->date('desde'), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when($request->date('hasta'), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('sat.transacciones.index', [
            'transactions' => $transactions,
            'endpoints' => SatEndpoint::cases(),
            'usuarios' => $puedeVerTodo ? User::orderBy('name')->get(['id', 'name']) : collect(),
            'filtros' => $request->only(['endpoint', 'resultado', 'desde', 'hasta']),
        ]);
    }

    public function show(SatTransaction $transaction): View
    {
        $this->authorize('view', $transaction);

        return view('sat.transacciones.show', ['transaction' => $transaction]);
    }
}
