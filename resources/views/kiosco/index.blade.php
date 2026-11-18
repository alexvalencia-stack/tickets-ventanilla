@extends('layouts.app')
@section('titulo', 'Elija su trámite')
@push('head')<meta http-equiv="refresh" content="300">@endpush {{-- actualiza horarios y cupos en el kiosco --}}

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>¿Qué trámite va a realizar?</h1>
            <p class="suave">Toque un trámite para ver los documentos que necesita y sacar su turno.</p>
        </div>
        <span class="etiqueta num">{{ now()->translatedFormat('l j \d\e F') }} · {{ now()->format('H:i') }}</span>
    </div>

    <a class="opcion" href="{{ route('kiosco.recuperar') }}" style="min-height:0;flex-direction:row;align-items:center;gap:14px">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true" style="width:1.6em;height:1.6em;color:var(--acento)"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <span style="flex:1;color:var(--texto)"><strong>¿Ya sacó su turno y lo perdió?</strong><br>
            <span class="suave">Recupérelo con su número de cédula.</span></span>
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--acento)"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>

    @forelse ($grupos as $categoria => $tramites)
        @php
            $fecha = $fechas[$tramites->first()->id];
            $uniforme = $tramites->every(fn ($t) => $fechas[$t->id] === $fecha);
            $limite = $tramites->first()->horaLimiteCorta();
        @endphp
        <section class="grupo" aria-labelledby="grupo-{{ $categoria }}">
            <h2 id="grupo-{{ $categoria }}">
                {{ \App\Models\Tramite::CATEGORIAS[$categoria][0] }}
                @if ($uniforme && ! $fecha)
                    <span class="etiqueta cerrado">Sin turnos disponibles estos días</span>
                @elseif ($uniforme && $fecha !== $hoy)
                    <span class="etiqueta aviso">Hoy ya no hay turnos · se entregan para el {{ \Illuminate\Support\Carbon::parse($fecha)->translatedFormat('l j \d\e F') }}</span>
                @elseif ($uniforme && $limite)
                    <span class="etiqueta ok">Turnos para hoy hasta las {{ $limite }}</span>
                @endif
            </h2>
            <div class="rejilla">
                @foreach ($tramites as $tramite)
                    @php $f = $fechas[$tramite->id]; @endphp
                    <a class="opcion {{ $f ? '' : 'cerrada' }}" href="{{ route('kiosco.tramite', $tramite) }}">
                        <strong>{{ $tramite->nombre }}</strong>
                        <span>{{ $tramite->descripcion }}</span>
                        <em>
                            @if (! $f) Ver requisitos
                            @elseif ($f === $hoy) Ver requisitos y sacar turno
                            @else Ver requisitos y sacar turno para el {{ \Illuminate\Support\Carbon::parse($f)->translatedFormat('l j') }}
                            @endif
                            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </em>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="tarjeta">No hay trámites configurados todavía.</div>
    @endforelse
@endsection
