@extends('layouts.app')
@section('titulo', $item->exists ? 'Editar trámite' : 'Nuevo trámite')
@section('ancho', 'estrecho')

@section('contenido')
    <a class="volver" href="{{ route('admin.tramites') }}">← Trámites</a>
    <div class="encabezado"><h1>{{ $item->exists ? 'Editar trámite' : 'Nuevo trámite' }}</h1></div>

    <form class="tarjeta" method="post" action="{{ route('admin.tramites.guardar', $item->id) }}">
        @csrf
        <div class="campo">
            <label for="nombre">Nombre *</label>
            <input id="nombre" name="nombre" value="{{ old('nombre', $item->nombre) }}" maxlength="120" required>
        </div>
        <div class="campo">
            <label for="descripcion">Descripción corta</label>
            <input id="descripcion" name="descripcion" value="{{ old('descripcion', $item->descripcion) }}" maxlength="255">
        </div>
        <div class="dos">
            <div class="campo">
                <label for="categoria">Tipo *</label>
                <select id="categoria" name="categoria" required>
                    @foreach (\App\Models\Tramite::CATEGORIAS as $clave => [$titulo, $prefijo])
                        <option value="{{ $clave }}" @selected(old('categoria', $item->categoria) === $clave)>{{ $titulo }} (turnos {{ $prefijo }}-001…)</option>
                    @endforeach
                </select>
            </div>
            <div class="campo">
                <label for="hora_limite">Se entregan turnos hasta</label>
                <input id="hora_limite" name="hora_limite" type="time" value="{{ old('hora_limite', $item->horaLimiteCorta()) }}">
                <small>Vacío = todo el día.</small>
            </div>
            <div class="campo">
                <label for="orden">Orden en el kiosco *</label>
                <input id="orden" name="orden" type="number" min="0" max="999" value="{{ old('orden', $item->orden ?? 0) }}" required>
            </div>
        </div>
        <div class="campo">
            <label for="requisitos">Requisitos</label>
            <textarea id="requisitos" name="requisitos" maxlength="5000">{{ old('requisitos', $item->requisitos) }}</textarea>
            <small>Un documento por línea. Así los verá el ciudadano antes de sacar el turno.</small>
        </div>
        <label class="campo-check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $item->activo ?? true))> Visible en el kiosco</label>
        <div class="fila-acciones">
            <button class="btn">Guardar</button>
            <a class="btn secundario" href="{{ route('admin.tramites') }}">Cancelar</a>
        </div>
    </form>
@endsection
