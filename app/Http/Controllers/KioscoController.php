<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Turno;
use App\Rules\Cedula;
use Illuminate\Http\Request;

class KioscoController extends Controller
{
    public function index()
    {
        $tramites = Tramite::where('activo', true)->orderBy('orden')->orderBy('nombre')->get();

        // Día en que se atendería cada trámite. Los de un mismo tipo y horario comparten resultado.
        $memo = $fechas = [];
        foreach ($tramites as $t) {
            $fechas[$t->id] = ($memo["$t->categoria|$t->hora_limite"] ??= [Turno::fechaPara($t)])[0];
        }

        return view('kiosco.index', [
            'fechas' => $fechas,
            'hoy' => today()->toDateString(),
            // Lo que se atiende hoy mismo va arriba (por la tarde, las entregas).
            'grupos' => $tramites->groupBy('categoria')
                ->sortBy(fn ($grupo) => ! $grupo->contains(fn ($t) => $fechas[$t->id] === today()->toDateString())),
        ]);
    }

    public function show(Tramite $tramite)
    {
        abort_unless($tramite->activo, 404);

        return view('kiosco.tramite', [
            'tramite' => $tramite,
            'fecha' => Turno::fechaPara($tramite),
            'hoy' => today()->toDateString(),
            'pideCedula' => $tramite->cupo() !== null,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'tramite_id' => ['required', 'exists:tramites,id'],
            'preferente' => ['nullable', 'boolean'],
            'cedula' => ['nullable', new Cedula],
        ]);
        $tramite = Tramite::findOrFail($datos['tramite_id']);
        $fecha = $tramite->activo ? Turno::fechaPara($tramite) : null;
        $cedula = null;

        if (! $fecha) {
            return back()->withErrors(['tramite_id' => 'No quedan turnos disponibles para este trámite en los próximos días.']);
        }

        // Los trámites con cupo llevan cédula: evita que una sola persona se lleve todos los turnos del día.
        if ($tramite->cupo() !== null) {
            $cedula = $datos['cedula'] ?? null;
            if (! $cedula) {
                return back()->withErrors(['cedula' => 'Escriba su número de cédula para sacar el turno.'])->withInput();
            }
            if (Turno::where('cedula', $cedula)->where('estado', 'espera')->where('fecha', '>=', today()->toDateString())->exists()) {
                return back()->withErrors(['cedula' => 'Esta cédula ya tiene un turno pendiente. Solo se permite uno a la vez.'])->withInput();
            }
        }

        return redirect()->route('kiosco.ticket', Turno::emitir($tramite, (bool) ($datos['preferente'] ?? false), $fecha, $cedula));
    }

    public function recuperar()
    {
        return view('kiosco.recuperar');
    }

    /** Vuelve a mostrar el turno pendiente de una cédula (se perdió el papel o el celular). */
    public function buscar(Request $request)
    {
        $datos = $request->validate(['cedula' => ['required', new Cedula]]);

        $turno = Turno::where('cedula', $datos['cedula'])
            ->whereIn('estado', ['espera', 'llamado'])
            ->where('fecha', '>=', today()->toDateString())
            ->orderBy('fecha')->first();

        if (! $turno) {
            return back()->withErrors(['cedula' => 'No encontramos un turno pendiente con esa cédula. Si ya fue atendido o la fecha pasó, saque un turno nuevo.'])->withInput();
        }

        return redirect()->route('kiosco.ticket', $turno);
    }

    public function ticket(Turno $turno)
    {
        $delante = $turno->estado !== 'espera' || $turno->fecha !== today()->toDateString() ? 0
            : Turno::hoy()->where('estado', 'espera')
                ->where(function ($q) use ($turno) {
                    $q->where(fn ($q) => $q->where('preferente', $turno->preferente)->where('id', '<', $turno->id));
                    if (! $turno->preferente) {
                        $q->orWhere('preferente', true);
                    }
                })->count();

        return view('kiosco.ticket', ['turno' => $turno->load('tramite', 'ventanilla'), 'delante' => $delante]);
    }
}
