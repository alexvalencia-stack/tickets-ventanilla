<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\KioscoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PantallaController;
use App\Http\Controllers\VentanillaController;
use Illuminate\Support\Facades\Route;

// Kiosco (público)
Route::get('/', [KioscoController::class, 'index'])->name('kiosco');
Route::get('/tramites/{tramite}', [KioscoController::class, 'show'])->name('kiosco.tramite');
Route::post('/turnos', [KioscoController::class, 'store'])->middleware('throttle:30,1')->name('kiosco.emitir');
// Throttle: evita que alguien pruebe cédulas una tras otra para ver los turnos de otros.
Route::get('/recuperar', [KioscoController::class, 'recuperar'])->name('kiosco.recuperar');
Route::post('/recuperar', [KioscoController::class, 'buscar'])->middleware('throttle:10,1')->name('kiosco.buscar');
Route::get('/turnos/{turno:token}', [KioscoController::class, 'ticket'])->name('kiosco.ticket');

// Pantalla de la sala de espera (pública)
Route::get('/pantalla', [PantallaController::class, 'index'])->name('pantalla');
Route::get('/pantalla/datos', [PantallaController::class, 'datos'])->name('pantalla.datos');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ventanilla', [VentanillaController::class, 'index'])->name('ventanilla');
    Route::get('/ventanilla/datos', [VentanillaController::class, 'datos'])->name('ventanilla.datos');
    Route::post('/ventanilla/elegir', [VentanillaController::class, 'elegir'])->name('ventanilla.elegir');
    Route::post('/ventanilla/llamar', [VentanillaController::class, 'llamar'])->name('ventanilla.llamar');
    Route::post('/ventanilla/turnos/{turno}/{accion}', [VentanillaController::class, 'accion'])
        ->whereIn('accion', ['rellamar', 'atendido', 'ausente'])->name('ventanilla.accion');

    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('inicio');
        Route::post('/cupos', [AdminController::class, 'cupos'])->name('cupos');
        foreach (['tramites', 'ventanillas', 'usuarios'] as $recurso) {
            Route::get("/$recurso", [AdminController::class, 'lista'])->name($recurso)->defaults('recurso', $recurso);
            Route::get("/$recurso/nuevo", [AdminController::class, 'form'])->name("$recurso.nuevo")->defaults('recurso', $recurso);
            Route::get("/$recurso/{id}", [AdminController::class, 'form'])->whereNumber('id')->name("$recurso.editar")->defaults('recurso', $recurso);
            Route::post("/$recurso/{id?}", [AdminController::class, 'guardar'])->whereNumber('id')->name("$recurso.guardar")->defaults('recurso', $recurso);
            Route::delete("/$recurso/{id}", [AdminController::class, 'eliminar'])->whereNumber('id')->name("$recurso.eliminar")->defaults('recurso', $recurso);
        }
    });
});
