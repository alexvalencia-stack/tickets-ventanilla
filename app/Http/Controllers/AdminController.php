<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Turno;
use App\Models\User;
use App\Models\Ventanilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    private const MODELOS = ['tramites' => Tramite::class, 'ventanillas' => Ventanilla::class, 'usuarios' => User::class];

    public function index(Request $request)
    {
        $fecha = $request->date('fecha')?->toDateString() ?? today()->toDateString();
        $turnos = Turno::where('fecha', $fecha)->with('tramite')->get();
        // Los reservados otro día no cuentan: su "espera" incluiría la noche.
        $llamados = $turnos->whereNotNull('llamado_at')->filter(fn ($t) => $t->created_at->toDateString() === $t->fecha);
        $finalizados = $turnos->where('estado', 'atendido');

        return view('admin.inicio', [
            'fecha' => $fecha,
            'turnos' => $turnos,
            'esperaPromedio' => round($llamados->avg(fn ($t) => $t->created_at->diffInMinutes($t->llamado_at)) ?? 0),
            'atencionPromedio' => round($finalizados->avg(fn ($t) => $t->llamado_at->diffInMinutes($t->finalizado_at)) ?? 0),
            'porTramite' => $turnos->groupBy('tramite.nombre')->map->count()->sortDesc(),
        ]);
    }

    public function lista(Request $request)
    {
        $recurso = $request->route('recurso');
        $items = match ($recurso) {
            'tramites' => Tramite::orderBy('orden')->orderBy('nombre')->get(),
            'ventanillas' => Ventanilla::orderBy('nombre')->get(),
            'usuarios' => User::with('ventanilla')->orderBy('name')->get(),
        };

        return view("admin.$recurso", ['items' => $items, 'cupos' => DB::table('cupos')->pluck('cupo', 'categoria')]);
    }

    /** Máximo de turnos por día para cada tipo de trámite. Vacío = sin límite. */
    public function cupos(Request $request)
    {
        $datos = $request->validate(['cupo' => ['array'], 'cupo.*' => ['nullable', 'integer', 'min:1', 'max:999']]);

        foreach (array_keys(Tramite::CATEGORIAS) as $categoria) {
            DB::table('cupos')->updateOrInsert(['categoria' => $categoria], ['cupo' => $datos['cupo'][$categoria] ?? null]);
        }

        return back()->with('aviso', 'Cupos guardados.');
    }

    public function form(Request $request)
    {
        // Laravel pasa los parámetros de ruta por posición, no por nombre: se leen de la ruta.
        [$recurso, $id] = [$request->route('recurso'), $request->route('id')];
        $clase = self::MODELOS[$recurso];

        return view("admin.$recurso-form", [
            'item' => $id ? $clase::findOrFail($id) : new $clase,
            'ventanillas' => Ventanilla::orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $request)
    {
        // Laravel pasa los parámetros de ruta por posición, no por nombre: se leen de la ruta.
        [$recurso, $id] = [$request->route('recurso'), $request->route('id')];
        $clase = self::MODELOS[$recurso];
        $item = $id ? $clase::findOrFail($id) : new $clase;
        $categorias = Rule::in(array_keys(Tramite::CATEGORIAS));

        $datos = $request->validate(match ($recurso) {
            'tramites' => [
                'nombre' => ['required', 'string', 'max:120'],
                'descripcion' => ['nullable', 'string', 'max:255'],
                'categoria' => ['required', $categorias],
                'requisitos' => ['nullable', 'string', 'max:5000'],
                'hora_limite' => ['nullable', 'date_format:H:i'],
                'orden' => ['required', 'integer', 'min:0', 'max:999'],
            ],
            'ventanillas' => [
                'nombre' => ['required', 'string', 'max:60'],
                'categoria' => ['nullable', $categorias],
            ],
            'usuarios' => [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($item)],
                'rol' => ['required', Rule::in(['admin', 'operador'])],
                'ventanilla_id' => ['nullable', 'exists:ventanillas,id'],
                'password' => [$id ? 'nullable' : 'required', Password::min(8)],
            ],
        });

        if ($recurso === 'usuarios') {
            if (empty($datos['password'])) {
                unset($datos['password']); // en blanco = conservar la clave actual
            }
            if ($item->is($request->user()) && $datos['rol'] !== 'admin') {
                return back()->withErrors(['rol' => 'No puede quitarse a sí mismo el rol de administrador.'])->withInput();
            }
        } else {
            $datos['activo'] = $request->boolean('activo');
        }

        $item->fill($datos)->save();

        return redirect()->route("admin.$recurso")->with('aviso', 'Cambios guardados.');
    }

    public function eliminar(Request $request)
    {
        // Laravel pasa los parámetros de ruta por posición, no por nombre: se leen de la ruta.
        [$recurso, $id] = [$request->route('recurso'), $request->route('id')];
        $item = self::MODELOS[$recurso]::findOrFail($id);
        if ($item->is($request->user())) {
            return back()->withErrors(['eliminar' => 'No puede eliminar su propia cuenta.']);
        }

        if ($recurso === 'tramites' && Turno::where('tramite_id', $item->id)->exists()) {
            return back()->withErrors(['eliminar' => 'No se puede eliminar porque ya tiene turnos registrados. Desactívelo en su lugar.']);
        }
        $item->delete();

        return back()->with('aviso', 'Eliminado.');
    }
}
