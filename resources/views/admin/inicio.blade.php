@extends('layouts.app')
@section('titulo', 'Resumen')

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Resumen del día</h1>
            <p class="suave">{{ \Illuminate\Support\Carbon::parse($fecha)->translatedFormat('l j \d\e F \d\e Y') }}</p>
        </div>
        <form method="get" class="fila-acciones">
            <div class="campo" style="margin:0">
                <label for="fecha" class="salto">Fecha</label>
                <input id="fecha" type="date" name="fecha" value="{{ $fecha }}" max="{{ today()->addDays(10)->toDateString() }}" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="indicadores">
        <div class="indicador"><b>{{ $turnos->count() }}</b><span>Turnos emitidos</span></div>
        <div class="indicador"><b>{{ $turnos->where('estado', 'espera')->count() }}</b><span>En espera</span></div>
        <div class="indicador"><b>{{ $turnos->where('estado', 'atendido')->count() }}</b><span>Atendidos</span></div>
        <div class="indicador"><b>{{ $turnos->where('estado', 'ausente')->count() }}</b><span>No se presentaron</span></div>
        <div class="indicador"><b>{{ $esperaPromedio }} min</b><span>Espera promedio</span></div>
        <div class="indicador"><b>{{ $atencionPromedio }} min</b><span>Atención promedio</span></div>
    </div>

    <div class="tabla-caja">
        <table>
            <thead><tr><th>Trámite</th><th class="der">Turnos</th></tr></thead>
            <tbody>
            @forelse ($porTramite as $nombre => $cantidad)
                <tr><td>{{ $nombre }}</td><td class="der num">{{ $cantidad }}</td></tr>
            @empty
                <tr><td colspan="2" class="suave">No se emitieron turnos este día.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
