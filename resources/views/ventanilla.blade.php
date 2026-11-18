@extends('layouts.app')
@section('titulo', 'Mi ventanilla')

@section('contenido')
    <div class="encabezado">
        <div>
            <h1>{{ $ventanilla?->nombre ?? 'Elija su ventanilla' }}</h1>
            <p class="suave">
                @if ($ventanilla?->categoria) Atiende solo: {{ \App\Models\Tramite::CATEGORIAS[$ventanilla->categoria][0] }} ·@endif
                Atendidos por usted hoy: <strong class="num">{{ $atendidosHoy }}</strong>
            </p>
        </div>
        <form method="post" action="{{ route('ventanilla.elegir') }}" class="fila-acciones">
            @csrf
            <label for="ventanilla_id" class="suave">Estoy en</label>
            <div class="campo" style="margin:0">
                <select id="ventanilla_id" name="ventanilla_id" onchange="this.form.submit()" @disabled($actual)>
                    <option value="" disabled @selected(! $ventanilla)>Seleccione…</option>
                    @foreach ($ventanillas as $v)
                        <option value="{{ $v->id }}" @selected($v->id === $ventanilla?->id)>{{ $v->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="puesto">
        <section class="tarjeta actual" aria-live="polite">
            @if ($actual)
                <p class="suave">Atendiendo ahora</p>
                <div class="codigo">{{ $actual->codigo }}</div>
                @if ($actual->preferente)<p><span class="etiqueta ok">Atención preferente</span></p>@endif
                <p><strong>{{ $actual->tramite->nombre }}</strong></p>
                @if ($actual->cedula)<p class="num">Cédula <strong>{{ $actual->cedula }}</strong></p>@endif
                <p class="suave num">Llamado a las {{ $actual->llamado_at->format('H:i') }} · {{ $actual->llamadas }} {{ $actual->llamadas === 1 ? 'llamada' : 'llamadas' }}</p>

                <div class="acciones">
                    <form method="post" action="{{ route('ventanilla.accion', [$actual, 'atendido']) }}">@csrf
                        <button class="btn exito grande ancho">Finalizar atención</button>
                    </form>
                    <form method="post" action="{{ route('ventanilla.accion', [$actual, 'rellamar']) }}">@csrf
                        <button class="btn secundario">Volver a llamar</button>
                    </form>
                    <form method="post" action="{{ route('ventanilla.accion', [$actual, 'ausente']) }}">@csrf
                        <button class="btn peligro">No se presentó</button>
                    </form>
                </div>

                @if ($requisitos = $actual->tramite->listaRequisitos())
                    <details open>
                        <summary>Revisar documentos de la carpeta ({{ count($requisitos) }})</summary>
                        <ul class="requisitos">
                            @foreach ($requisitos as $requisito)
                                <li><label><input type="checkbox"> <span>{{ $requisito }}</span></label></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @else
                <p class="suave">En espera</p>
                <div class="codigo" id="en-espera">{{ $enEspera }}</div>
                <p class="suave">{{ $enEspera === 1 ? 'persona esperando' : 'personas esperando' }}</p>
                <div class="acciones">
                    <form method="post" action="{{ route('ventanilla.llamar') }}">@csrf
                        <button class="btn grande ancho" @disabled(! $ventanilla)>Llamar siguiente</button>
                    </form>
                </div>
            @endif
        </section>

        <aside class="tarjeta">
            <h2>Siguientes en la fila</h2>
            <ul class="cola">
                @forelse ($cola as $turno)
                    <li>
                        <b>{{ $turno->codigo }}</b>
                        <span>{{ $turno->tramite->nombre }}</span>
                        @if ($turno->preferente)<span class="etiqueta ok" style="flex:none">Preferente</span>@endif
                        <span class="num" style="flex:none">{{ $turno->created_at->format('H:i') }}</span>
                    </li>
                @empty
                    <li><span>No hay nadie esperando.</span></li>
                @endforelse
            </ul>
        </aside>
    </div>
@endsection

@unless ($actual)
    @push('scripts')
        <script>
            // Mientras no atiende a nadie, la página se refresca sola cuando cambia la fila.
            const inicial = {{ $enEspera }};
            setInterval(async () => {
                try {
                    const r = await fetch('{{ route('ventanilla.datos') }}', {headers: {Accept: 'application/json'}});
                    if (r.ok && (await r.json()).en_espera !== inicial) location.reload();
                } catch {}
            }, 5000);
        </script>
    @endpush
@endunless
