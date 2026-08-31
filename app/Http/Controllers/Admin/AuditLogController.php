<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->string('accion')->trim()->value(), fn ($q, string $a) => $q->where('action', $a))
            ->when($request->integer('usuario'), fn ($q, int $id) => $q->where('user_id', $id))
            ->when($request->date('desde'), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when($request->date('hasta'), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.bitacora.index', [
            'logs' => $logs,
            'acciones' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'filtros' => $request->only(['accion', 'usuario', 'desde', 'hasta']),
        ]);
    }
}
