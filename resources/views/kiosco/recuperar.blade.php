@extends('layouts.app')
@section('titulo', 'Recuperar mi turno')
@section('ancho', 'estrecho')

@section('contenido')
    <a class="volver" href="{{ route('kiosco') }}">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        Volver al inicio
    </a>
    <div class="encabezado">
        <div>
            <h1>Recuperar mi turno</h1>
            <p class="suave">Si perdió el papel o el celular, escriba la cédula con la que sacó el turno y lo volverá a ver.</p>
        </div>
    </div>

    <form class="tarjeta" method="post" action="{{ route('kiosco.buscar') }}">
        @csrf
        <div class="campo">
            <label for="cedula">Número de cédula *</label>
            <input id="cedula" name="cedula" value="{{ old('cedula') }}" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autocomplete="off" required autofocus
                   @error('cedula') aria-invalid="true" aria-describedby="cedula-error" @enderror>
            @error('cedula')<span class="error" id="cedula-error">{{ $message }}</span>@enderror
            <small>Solo se recuperan turnos pendientes. Los de entrega de documentos y consultas no piden cédula y no se pueden recuperar: saque uno nuevo.</small>
        </div>
        <button class="btn grande">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            Buscar mi turno
        </button>
    </form>
@endsection
