<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CredentialAssignmentController;
use App\Http\Controllers\Admin\SatCredentialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sat\CuscarFileController;
use App\Http\Controllers\Sat\ManifiestoController;
use App\Http\Controllers\Sat\SatTransactionController;
use App\Http\Controllers\Sat\MiCredencialSatController;
use App\Http\Controllers\Sat\ValidarCuscarController;
use App\Http\Controllers\Sat\ValidarNitController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('sat')->name('sat.')->group(function () {
        // Pantalla 5: cambiar la clave SAT. No lleva el middleware de credencial
        // porque es justo donde se avisa al usuario que no tiene ninguna.
        Route::get('credencial', [MiCredencialSatController::class, 'edit'])
            ->name('credencial.edit');
        Route::patch('credencial', [MiCredencialSatController::class, 'update'])
            ->middleware(['can:sat.cambiar-clave', 'password.confirm'])
            ->name('credencial.update');
        Route::post('credencial/probar', [MiCredencialSatController::class, 'probar'])
            ->middleware(['can:sat.cambiar-clave', 'sat.credencial', 'throttle:20,1'])
            ->name('credencial.probar');

        // El orden importa: primero se comprueba el permiso y después si el
        // usuario tiene credencial. Al revés, a quien no tiene permiso se le
        // pediría una credencial que de todas formas no podría usar.
        Route::middleware('throttle:20,1')->group(function () {
            // Pantalla 1: validar NIT.
            Route::get('validar-nit', [ValidarNitController::class, 'create'])
                ->middleware(['can:sat.validar-nit', 'sat.credencial'])->name('nit.create');
            Route::post('validar-nit', [ValidarNitController::class, 'store'])
                ->middleware(['can:sat.validar-nit', 'sat.credencial'])->name('nit.store');

            // Pantalla 2: validar cuscar.
            Route::get('validar-cuscar', [ValidarCuscarController::class, 'create'])
                ->middleware(['can:sat.validar-cuscar', 'sat.credencial'])->name('cuscar.validar.create');
            Route::post('validar-cuscar', [ValidarCuscarController::class, 'store'])
                ->middleware(['can:sat.validar-cuscar', 'sat.credencial'])->name('cuscar.validar.store');

            // Pantalla 3: agregar cuscar, en tres pasos.
            Route::get('cuscar', [CuscarFileController::class, 'create'])
                ->middleware(['can:sat.agregar-cuscar', 'sat.credencial'])->name('cuscar.create');
            Route::post('cuscar', [CuscarFileController::class, 'store'])
                ->middleware(['can:sat.agregar-cuscar', 'sat.credencial'])->name('cuscar.store');
            Route::post('cuscar/{cuscar}/enviar', [CuscarFileController::class, 'send'])
                ->middleware(['can:sat.agregar-cuscar', 'sat.credencial'])->name('cuscar.send');

            // Pantalla 4: consultar manifiesto.
            Route::get('manifiesto', [ManifiestoController::class, 'create'])
                ->middleware(['can:sat.consultar-manifiesto', 'sat.credencial'])->name('manifiesto.create');
            Route::post('manifiesto', [ManifiestoController::class, 'store'])
                ->middleware(['can:sat.consultar-manifiesto', 'sat.credencial'])->name('manifiesto.store');
        });

        Route::get('cuscar-historial', [CuscarFileController::class, 'index'])
            ->name('cuscar.index');
        Route::get('cuscar/{cuscar}', [CuscarFileController::class, 'show'])
            ->name('cuscar.show');
        Route::get('cuscar/{cuscar}/descargar', [CuscarFileController::class, 'download'])
            ->name('cuscar.download');
        Route::get('validar-cuscar/{cuscar}', [ValidarCuscarController::class, 'show'])
            ->name('cuscar.validar.show');

        Route::get('transacciones', [SatTransactionController::class, 'index'])
            ->name('transacciones.index');
        Route::get('transacciones/{transaction}', [SatTransactionController::class, 'show'])
            ->name('transacciones.show');

        Route::get('manifiestos', [ManifiestoController::class, 'index'])
            ->middleware('can:viewAny,App\Models\SatManifest')->name('manifiesto.index');
        Route::get('manifiestos/{manifest}', [ManifiestoController::class, 'show'])
            ->name('manifiesto.show');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:credenciales.manage')->group(function () {
            Route::resource('credenciales', SatCredentialController::class)
                ->parameters(['credenciales' => 'credential']);

            Route::post('credenciales/{credential}/asignar', [CredentialAssignmentController::class, 'store'])
                ->name('credenciales.asignar');
            Route::delete('asignaciones/{user}', [CredentialAssignmentController::class, 'destroy'])
                ->name('asignaciones.destroy');
        });

        Route::get('bitacora', [AuditLogController::class, 'index'])
            ->middleware('can:bitacora.view')->name('bitacora.index');

        Route::resource('usuarios', UserController::class)
            ->middleware('can:usuarios.manage')
            ->parameters(['usuarios' => 'user'])
            ->except('show');
    });
});

require __DIR__.'/auth.php';
