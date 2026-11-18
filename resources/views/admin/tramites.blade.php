@extends('layouts.app')
@section('titulo', 'Trámites')

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>Trámites</h1>
            <p class="suave">Lo que el ciudadano ve en el kiosco, con sus requisitos y horario.</p>
        </div>
        <a class="btn" href="{{ route('admin.tramites.nuevo') }}">Nuevo trámite</a>
    </div>
    <form class="tarjeta" method="post" action="{{ route('admin.cupos') }}" style="margin-bottom:24px">
        @csrf
        <h2>Turnos por día</h2>
        <p class="suave" style="margin:4px 0 16px">Cuando se llena el cupo del día, o pasa la hora límite, el kiosco entrega turnos para el siguiente día hábil
            (hasta {{ \App\Models\Turno::DIAS_ADELANTO }} días). Vacío = sin límite.</p>
        <div class="dos">
            @foreach (\App\Models\Tramite::CATEGORIAS as $clave => [$titulo])
                <div class="campo">
                    <label for="cupo-{{ $clave }}">{{ $titulo }}</label>
                    <input id="cupo-{{ $clave }}" name="cupo[{{ $clave }}]" type="number" min="1" max="999" value="{{ old("cupo.$clave", $cupos[$clave] ?? '') }}" placeholder="Sin límite">
                </div>
            @endforeach
        </div>
        <button class="btn">Guardar cupos</button>
    </form>

    <div class="tabla-caja">
        <table>
            <thead><tr><th>Orden</th><th>Trámite</th><th>Tipo</th><th>Turnos hasta</th><th>Requisitos</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @foreach ($items as $t)
                <tr>
                    <td class="num">{{ $t->orden }}</td>
                    <td><strong>{{ $t->nombre }}</strong></td>
                    <td>{{ \App\Models\Tramite::CATEGORIAS[$t->categoria][0] }}</td>
                    <td class="num">{{ $t->horaLimiteCorta() ?? 'Todo el día' }}</td>
                    <td class="num">{{ count($t->listaRequisitos()) }}</td>
                    <td><span class="etiqueta {{ $t->activo ? 'ok' : '' }}">{{ $t->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td class="der"><a class="btn secundario chico" href="{{ route('admin.tramites.editar', $t->id) }}">Editar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
