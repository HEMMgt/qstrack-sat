<?php

namespace App\Http\Controllers\Sat;

use App\Enums\CuscarStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sat\StoreCuscarFileRequest;
use App\Models\CuscarFile;
use App\Models\SatCredential;
use App\Rules\CuscarFileName;
use App\Services\Sat\Exceptions\SatException;
use App\Services\Sat\SatClientFactory;
use App\Services\Sat\Support\CuscarContent;
use App\Services\Sat\Support\CuscarHeader;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Alta y envío de archivos cuscar, en los tres pasos del sistema original:
 * subir, revisar y transmitir.
 */
class CuscarFileController extends Controller
{
    public function __construct(private readonly SatClientFactory $clients) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CuscarFile::class);

        $files = CuscarFile::query()
            ->with('user')
            ->unless($request->user()->isAdmin(), fn ($q) => $q->ownedBy($request->user()))
            ->latest()
            ->paginate(20);

        return view('sat.cuscar.index', ['files' => $files]);
    }

    /** Paso 1: elegir el archivo. */
    public function create(): View
    {
        return view('sat.cuscar.create');
    }

    /** Paso 2: validar el nombre y guardarlo en el almacén privado. */
    public function store(StoreCuscarFileRequest $request): RedirectResponse
    {
        $upload = $request->file('archivo');
        $name = $upload->getClientOriginalName();
        $user = $request->user();

        // El nombre en disco lleva un identificador único delante para que dos
        // cargas del mismo cuscar no se pisen.
        $path = $upload->storeAs(
            (string) $user->id,
            Str::ulid().'_'.$name,
            config('sat.cuscar.disk'),
        );

        $cabecera = CuscarHeader::fromContent(
            Storage::disk(config('sat.cuscar.disk'))->get($path),
        );

        $file = CuscarFile::create([
            'user_id' => $user->id,
            'sat_credential_id' => $user->satCredential()->id,
            'filename' => $name,
            'size_bytes' => $upload->getSize(),
            'sha256' => hash_file('sha256', $upload->getRealPath() ?: $upload->getPathname()),
            'storage_path' => $path,
            'status' => CuscarStatus::Cargado,
            'emisor' => $cabecera->emisor,
            'numero_manifiesto_declarado' => $cabecera->numeroManifiesto,
        ] + CuscarFileName::parse($name));

        AuditLogger::log('sat.cuscar.cargado', $file, "Se cargó el archivo {$file->filename}", [
            'sha256' => $file->sha256,
            'size_bytes' => $file->size_bytes,
        ]);

        return redirect()
            ->route('sat.cuscar.show', $file)
            ->with('status', 'Archivo cargado. Revíselo antes de transmitirlo a la SAT.');
    }

    /** Paso 2b: revisar el contenido antes de transmitir. */
    public function show(CuscarFile $cuscar): View
    {
        $this->authorize('view', $cuscar);

        $preview = null;
        $missing = ! $cuscar->exists();

        if (! $missing) {
            // Se muestra ya normalizado: es lo que se transmitirá, no los bytes
            // crudos, que pueden venir en UTF-16 y resultar ilegibles.
            $texto = CuscarContent::toPlainText($cuscar->contents());
            $lines = preg_split('/\r\n|\n|\r/', $texto) ?: [];
            $limit = (int) config('sat.cuscar.preview_lines');
            $preview = [
                'lines' => array_slice($lines, 0, $limit),
                'truncated' => count($lines) > $limit,
                'total' => count($lines),
            ];
        }

        return view('sat.cuscar.show', [
            'file' => $cuscar,
            'preview' => $preview,
            'missing' => $missing,
            // La credencial con la que se transmitirá, para que el operador vea
            // el emisor del archivo y la empresa emisora una al lado de la otra.
            'credencial' => request()->user()->satCredential(),
        ]);
    }

    /** Paso 3: transmitir a la SAT. */
    public function send(Request $request, CuscarFile $cuscar): RedirectResponse
    {
        $this->authorize('send', $cuscar);

        if (! $cuscar->exists()) {
            return back()->with('sat_error', 'El archivo ya no está disponible en el servidor.');
        }

        // Reenviar puede duplicar un manifiesto en la SAT, así que exige una
        // confirmación explícita.
        $reenvio = $cuscar->wasSent();

        if ($reenvio && ! $request->boolean('reenviar')) {
            return back()->with(
                'sat_error',
                'Este archivo ya fue transmitido. Marque la casilla de reenvío si realmente desea repetirlo.',
            );
        }

        $credencial = $request->user()->satCredential();

        // La SAT exige que el emisor declarado en el UNB corresponda a la
        // empresa con cuyas credenciales se transmite. Enviarlo de todas formas
        // provoca un rechazo en el segmento de cabecera que no dice cuál es el
        // problema real.
        if (! $credencial->admiteEmisor($cuscar->emisor)) {
            $correcta = SatCredential::deEmisor($cuscar->emisor, exceptoId: $credencial->id);

            return back()->with('sat_error', sprintf(
                'El archivo declara el emisor %s y usted transmite como %s. %s',
                $cuscar->emisor,
                $credencial->label(),
                $correcta
                    ? "Ese emisor corresponde a {$correcta->name}: solicite esa credencial."
                    : 'La SAT lo rechazaría: solicite la credencial que corresponde a ese emisor.',
            ));
        }

        // Lo que se envía es el archivo en disco, no lo que mande el navegador:
        // el sistema legacy lo cargaba en un textarea editable.
        $contenido = CuscarContent::prepare($cuscar->contents());

        try {
            $response = $this->clients->forUser($request->user())
                ->ingresarCuscar($cuscar->filename, $contenido);
        } catch (SatException $e) {
            return back()->with('sat_error', $e->userMessage());
        }

        $cuscar->update([
            'last_response_description' => $response->descripcion,
            'sat_transaction_id' => $response->transactionId,
        ]);

        if (! $response->isSuccess()) {
            $cuscar->update(['status' => CuscarStatus::Rechazado]);

            AuditLogger::log(
                'sat.cuscar.rechazado',
                $cuscar,
                "La SAT rechazó {$cuscar->filename}",
                ['referencia' => $response->transactionUuid],
            );

            return back()->with('sat_error', $response->descripcion);
        }

        $cuscar->update([
            'status' => CuscarStatus::Enviado,
            'sent_at' => now(),
            'firma_electronica' => $response->manifiesto->firmaElectronica,
            'numero_manifiesto' => $response->manifiesto->numeroManifiesto,
        ]);

        AuditLogger::log(
            $reenvio ? 'sat.cuscar.reenviado' : 'sat.cuscar.enviado',
            $cuscar,
            "Se transmitió {$cuscar->filename} a la SAT",
            [
                'referencia' => $response->transactionUuid,
                'numero_manifiesto' => $cuscar->numero_manifiesto,
            ],
        );

        return redirect()
            ->route('sat.cuscar.validar.create', ['nombreArchivo' => $cuscar->filename])
            ->with('status', $response->descripcion)
            ->with('recien_enviado', true);
    }

    public function download(CuscarFile $cuscar): StreamedResponse
    {
        $this->authorize('download', $cuscar);

        abort_unless($cuscar->exists(), 404);

        AuditLogger::log('sat.cuscar.descargado', $cuscar, "Se descargó {$cuscar->filename}");

        return $cuscar->disk()->download($cuscar->storage_path, $cuscar->filename);
    }
}
