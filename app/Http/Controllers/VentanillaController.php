<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use App\Models\Ventanilla;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VentanillaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $ventanilla = $user->ventanilla;

        return view('ventanilla', [
            'ventanilla' => $ventanilla,
            'ventanillas' => Ventanilla::where('activo', true)->orderBy('nombre')->get(),
            'actual' => $this->actual($request),
            'cola' => Turno::enCola($ventanilla?->categoria)->with('tramite')->limit(8)->get(),
            'enEspera' => Turno::enCola($ventanilla?->categoria)->count(),
            'atendidosHoy' => Turno::hoy()->where('user_id', $user->id)->where('estado', 'atendido')->count(),
        ]);
    }

    public function datos(Request $request)
    {
        return ['en_espera' => Turno::enCola($request->user()->ventanilla?->categoria)->count()];
    }

    public function elegir(Request $request)
    {
        $datos = $request->validate([
            'ventanilla_id' => ['required', Rule::exists('ventanillas', 'id')->where('activo', true)],
        ]);
        if ($this->actual($request)) {
            return back()->withErrors(['ventanilla_id' => 'Finalice el turno actual antes de cambiar de ventanilla.']);
        }
        $request->user()->update($datos);

        return back();
    }

    public function llamar(Request $request)
    {
        $user = $request->user();
        if (! $user->ventanilla) {
            return back()->withErrors(['ventanilla_id' => 'Elija primero su ventanilla.']);
        }
        if ($this->actual($request)) {
            return back()->withErrors(['turno' => 'Finalice el turno actual antes de llamar al siguiente.']);
        }
        if (! Turno::llamarSiguiente($user->ventanilla, $user)) {
            return back()->with('aviso', 'No hay turnos en espera.');
        }

        return back();
    }

    public function accion(Request $request, Turno $turno, string $accion)
    {
        abort_unless($turno->user_id === $request->user()->id && $turno->estado === 'llamado', 403);

        if ($accion === 'rellamar') {
            $turno->increment('llamadas');
        } else {
            $turno->update(['estado' => $accion, 'finalizado_at' => now()]);
        }

        return back();
    }

    private function actual(Request $request): ?Turno
    {
        return Turno::hoy()->where('user_id', $request->user()->id)->where('estado', 'llamado')
            ->with('tramite')->latest('llamado_at')->first();
    }
}
