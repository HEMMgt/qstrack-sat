<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sat\ConsultarManifiestoRequest;
use App\Models\SatManifest;
use App\Services\Sat\Exceptions\SatException;
use App\Services\Sat\SatClientFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\AuditLogger;

class ManifiestoController extends Controller
{
    public function __construct(private readonly SatClientFactory $clients) {}

    public function index(Request $request): View
    {
        $manifests = SatManifest::query()
            ->with('user')
            ->unless($request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($request->string('buscar')->trim()->value(), fn ($q, string $term) => $q
                ->where('numero_manifiesto_consultado', 'like', "%{$term}%"))
            ->latest('queried_at')
            ->paginate(20)
            ->withQueryString();

        return view('sat.manifiesto.index', [
            'manifests' => $manifests,
            'buscar' => $request->string('buscar')->trim()->value(),
        ]);
    }

    public function create(): View
    {
        return view('sat.manifiesto.create');
    }

    public function store(ConsultarManifiestoRequest $request): RedirectResponse
    {
        $numero = $request->validated('numeroManifiesto');
        $user = $request->user();

        try {
            $response = $this->clients->forUser($user)->consultarEncabezadoManifiesto($numero);
        } catch (SatException $e) {
            return back()->withInput()->with('sat_error', $e->userMessage());
        }

        if (! $response->isSuccess()) {
            return back()->withInput()->with('sat_error', $response->descripcion);
        }

        $manifest = SatManifest::create([
            'user_id' => $user->id,
            'sat_credential_id' => $user->satCredential()?->id,
            'sat_transaction_id' => $response->transactionId,
            'numero_manifiesto_consultado' => $numero,
            'queried_at' => now(),
        ] + SatManifest::attributesFromManifiesto($response->manifiesto));

        AuditLogger::log(
            'sat.manifiesto.consultado',
            $manifest,
            "Se consultó el manifiesto {$numero}",
            ['referencia' => $response->transactionUuid],
        );

        return redirect()
            ->route('sat.manifiesto.show', $manifest)
            ->with('status', $response->descripcion);
    }

    public function show(SatManifest $manifest): View
    {
        $this->authorize('view', $manifest);

        return view('sat.manifiesto.show', ['manifest' => $manifest]);
    }
}
