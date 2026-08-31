<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las pantallas que hablan con la SAT necesitan una credencial asignada.
 *
 * Sustituye al mensaje "Falta la clave de acceso al WebService" del sistema
 * legacy, que dejaba la pantalla en blanco sin decir qué hacer.
 */
class EnsureUserHasSatCredential
{
    public function handle(Request $request, Closure $next): Response
    {
        $credential = $request->user()?->satCredential();

        if ($credential === null) {
            return redirect()
                ->route('sat.credencial.edit')
                ->with('sat_error', 'No tiene una credencial SAT asignada. Solicítela a un administrador.');
        }

        if (! $credential->is_active) {
            return redirect()
                ->route('sat.credencial.edit')
                ->with('sat_error', 'Su credencial SAT está desactivada. Contacte a un administrador.');
        }

        return $next($request);
    }
}
