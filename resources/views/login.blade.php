@extends('layouts.app')
@section('titulo', 'Ingreso del personal')

@section('contenido')
    <div class="tarjeta ingreso">
        <h1 style="font-size:1.5rem;margin-bottom:18px">Ingreso del personal</h1>
        <form method="post" action="{{ route('login') }}">
            @csrf
            <div class="campo">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <label class="campo-check"><input type="checkbox" name="recordar" value="1"> Mantener la sesión en este equipo</label>
            <button class="btn grande">Ingresar</button>
        </form>
    </div>
@endsection
