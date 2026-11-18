<?php

namespace App\Http\Controllers;

use App\Models\Turno;

class PantallaController extends Controller
{
    public function index()
    {
        return view('pantalla');
    }

    public function datos()
    {
        // Primero los que están siendo llamados ahora; después los últimos ya atendidos.
        $turnos = Turno::hoy()->whereNotNull('llamado_at')->with('ventanilla')
            ->orderByRaw("case when estado = 'llamado' then 0 else 1 end")
            ->orderByDesc('updated_at')->limit(6)->get();

        return [
            'turnos' => $turnos->map(fn (Turno $t) => [
                'clave' => "{$t->id}:{$t->llamadas}", // cambia cuando se vuelve a llamar
                'codigo' => $t->codigo,
                'ventanilla' => $t->ventanilla?->nombre ?? '—',
                'preferente' => $t->preferente,
                'activo' => $t->estado === 'llamado',
            ]),
            'en_espera' => Turno::hoy()->where('estado', 'espera')->count(),
        ];
    }
}
