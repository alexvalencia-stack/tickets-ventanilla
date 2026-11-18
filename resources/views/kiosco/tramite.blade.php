@extends('layouts.app')
@section('titulo', $tramite->nombre)
@section('ancho', 'estrecho')

@php $dia = $fecha ? \Illuminate\Support\Carbon::parse($fecha)->translatedFormat('l j \d\e F') : null; @endphp

@section('contenido')
    <a class="volver" href="{{ route('kiosco') }}">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        Elegir otro trámite
    </a>
    <div class="encabezado">
        <div>
            <h1>{{ $tramite->nombre }}</h1>
            <p class="suave">{{ $tramite->descripcion }}</p>
        </div>
    </div>

    @if (! $fecha)
        <div class="alerta aviso" role="status">
            No quedan turnos para este trámite en los próximos días. Puede revisar los requisitos y volver a intentar mañana.
        </div>
    @elseif ($fecha !== $hoy)
        <div class="alerta info" role="status">
            <div>
                <strong>Hoy ya no hay turnos para este trámite.</strong>
                Puede sacar uno para el <strong>{{ $dia }}</strong> y venir ese día con su carpeta completa.
            </div>
        </div>
    @endif

    <div class="tarjeta">
        <h2>Documentos que debe traer</h2>
        @if ($requisitos = $tramite->listaRequisitos())
            <p class="suave">Marque lo que ya tiene. Si le falta algo, es posible que su carpeta no sea recibida.</p>
            <ul class="requisitos">
                @foreach ($requisitos as $i => $requisito)
                    <li><label><input type="checkbox" name="req{{ $i }}"> <span>{{ $requisito }}</span></label></li>
                @endforeach
            </ul>
        @else
            <p class="suave">Este trámite no tiene requisitos registrados. Consulte en ventanilla.</p>
        @endif
    </div>

    @if ($fecha)
        <form method="post" action="{{ route('kiosco.emitir') }}">
            @csrf
            <input type="hidden" name="tramite_id" value="{{ $tramite->id }}">
            <label class="marca-check">
                <input type="checkbox" name="preferente" value="1" @checked(old('preferente'))>
                <span><strong>Atención preferente</strong>
                    <small>Adultos mayores, personas con discapacidad, embarazadas o con niños en brazos.</small></span>
            </label>
            @if ($pideCedula)
                <div class="campo">
                    <label for="cedula">Número de cédula *</label>
                    <input id="cedula" name="cedula" value="{{ old('cedula') }}" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autocomplete="off" required
                           @error('cedula') aria-invalid="true" aria-describedby="cedula-error" @enderror>
                    @error('cedula')<span class="error" id="cedula-error">{{ $message }}</span>@enderror
                    <small>10 dígitos. Los turnos de este trámite son limitados: cada persona puede tener uno pendiente.</small>
                </div>
            @endif
            <button class="btn grande">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/></svg>
                {{ $fecha === $hoy ? 'Sacar turno' : "Sacar turno para el $dia" }}
            </button>
        </form>
    @endif
@endsection
