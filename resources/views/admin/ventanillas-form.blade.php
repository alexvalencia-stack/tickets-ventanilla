@extends('layouts.app')
@section('titulo', $item->exists ? 'Editar ventanilla' : 'Nueva ventanilla')
@section('ancho', 'estrecho')

@section('contenido')
    <a class="volver" href="{{ route('admin.ventanillas') }}">← Ventanillas</a>
    <div class="encabezado"><h1>{{ $item->exists ? 'Editar ventanilla' : 'Nueva ventanilla' }}</h1></div>

    <form class="tarjeta" method="post" action="{{ route('admin.ventanillas.guardar', $item->id) }}">
        @csrf
        <div class="campo">
            <label for="nombre">Nombre *</label>
            <input id="nombre" name="nombre" value="{{ old('nombre', $item->nombre) }}" maxlength="60" required>
            <small>Así se muestra y se anuncia en la pantalla, por ejemplo «Ventanilla 4».</small>
        </div>
        <div class="campo">
            <label for="categoria">Atiende</label>
            <select id="categoria" name="categoria">
                <option value="">Todos los trámites</option>
                @foreach (\App\Models\Tramite::CATEGORIAS as $clave => [$titulo])
                    <option value="{{ $clave }}" @selected(old('categoria', $item->categoria) === $clave)>Solo: {{ $titulo }}</option>
                @endforeach
            </select>
        </div>
        <label class="campo-check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $item->activo ?? true))> Activa</label>
        <div class="fila-acciones">
            <button class="btn">Guardar</button>
            <a class="btn secundario" href="{{ route('admin.ventanillas') }}">Cancelar</a>
        </div>
    </form>
@endsection
