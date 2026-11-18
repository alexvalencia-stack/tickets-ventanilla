@extends('layouts.app')
@section('titulo', $item->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('ancho', 'estrecho')

@section('contenido')
    <a class="volver" href="{{ route('admin.usuarios') }}">← Usuarios</a>
    <div class="encabezado"><h1>{{ $item->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h1></div>

    <form class="tarjeta" method="post" action="{{ route('admin.usuarios.guardar', $item->id) }}">
        @csrf
        <div class="campo">
            <label for="name">Nombre *</label>
            <input id="name" name="name" value="{{ old('name', $item->name) }}" maxlength="120" required>
        </div>
        <div class="campo">
            <label for="email">Correo *</label>
            <input id="email" name="email" type="email" value="{{ old('email', $item->email) }}" autocomplete="off" required>
        </div>
        <div class="dos">
            <div class="campo">
                <label for="rol">Rol *</label>
                <select id="rol" name="rol" required>
                    <option value="operador" @selected(old('rol', $item->rol) !== 'admin')>Operador de ventanilla</option>
                    <option value="admin" @selected(old('rol', $item->rol) === 'admin')>Administrador</option>
                </select>
            </div>
            <div class="campo">
                <label for="ventanilla_id">Ventanilla habitual</label>
                <select id="ventanilla_id" name="ventanilla_id">
                    <option value="">Sin asignar</option>
                    @foreach ($ventanillas as $v)
                        <option value="{{ $v->id }}" @selected((int) old('ventanilla_id', $item->ventanilla_id) === $v->id)>{{ $v->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="campo">
            <label for="password">Contraseña {{ $item->exists ? '' : '*' }}</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" @required(! $item->exists)>
            <small>Mínimo 8 caracteres.{{ $item->exists ? ' Déjela vacía para no cambiarla.' : '' }}</small>
        </div>
        <div class="fila-acciones">
            <button class="btn">Guardar</button>
            <a class="btn secundario" href="{{ route('admin.usuarios') }}">Cancelar</a>
        </div>
    </form>
@endsection
