@extends('layouts.app')
@section('titulo', "Turno $turno->codigo")
@section('ancho', 'estrecho')
@if (in_array($turno->estado, ['espera', 'llamado']) && $turno->fecha === today()->toDateString())
    @push('head')<meta http-equiv="refresh" content="10">@endpush {{-- quien lo mira en el celular ve cuándo lo llaman --}}
@endif

@section('contenido')
    <div class="tarjeta ticket">
        <p class="suave">GAD Municipal · Catastros y Avalúos</p>
        <p><strong>Su turno</strong></p>
        <div class="codigo">{{ $turno->codigo }}</div>
        @if ($turno->preferente)<p><span class="etiqueta ok">Atención preferente</span></p>@endif
        <p><strong>{{ $turno->tramite->nombre }}</strong></p>
        @if ($turno->cedula)<p class="num">Cédula: <strong>{{ $turno->cedula }}</strong></p>@endif
        <p class="suave num">Emitido el {{ $turno->created_at->format('d/m/Y · H:i') }}</p>
        <hr>
        @if ($turno->esFuturo())
            <div class="alerta info" role="status" style="justify-content:center;margin:0 0 12px">
                <div>Válido solo el<br><strong style="font-size:1.4rem">{{ \Illuminate\Support\Carbon::parse($turno->fecha)->translatedFormat('l j \d\e F') }}</strong></div>
            </div>
            <p class="suave">Venga ese día con su carpeta completa y espere a que su número aparezca en la pantalla.</p>
        @elseif ($turno->fecha !== today()->toDateString())
            <p>Este turno fue válido solo el {{ \Illuminate\Support\Carbon::parse($turno->fecha)->format('d/m/Y') }}.</p>
        @elseif ($turno->estado === 'espera')
            <p class="num">
                @if ($delante === 0) <strong>Usted es el siguiente.</strong>
                @else Tiene <strong>{{ $delante }}</strong> {{ $delante === 1 ? 'persona' : 'personas' }} delante.
                @endif
            </p>
            <p class="suave">Espere a que su número aparezca en la pantalla.</p>
        @elseif ($turno->estado === 'llamado')
            <div class="alerta exito" role="status" style="justify-content:center;margin:0">
                <strong>Es su turno: pase a {{ $turno->ventanilla?->nombre ?? 'la ventanilla' }}</strong>
            </div>
        @elseif ($turno->estado === 'atendido')
            <p>Turno atendido. Gracias por su visita.</p>
        @else
            <p>Lo llamamos y no se presentó. Si aún necesita atención, saque un nuevo turno.</p>
        @endif

        <div class="acciones no-imprimir">
            @if ($turno->estado === 'espera')
                <button class="btn" type="button" id="descargar">
                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                    Guardar como imagen
                </button>
            @endif
            <button class="btn secundario" type="button" onclick="window.print()">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>
                Imprimir turno
            </button>
            <a class="btn secundario" href="{{ route('kiosco') }}">Volver al inicio</a>
        </div>
    </div>
@endsection

@if ($turno->estado === 'espera')
    @php
            $imagen = [
                'codigo' => $turno->codigo,
                'tramite' => $turno->tramite->nombre,
                'cedula' => $turno->cedula,
                'preferente' => $turno->preferente,
                'valido' => \Illuminate\Support\Carbon::parse($turno->fecha)->translatedFormat('l j \d\e F \d\e Y'),
                'emitido' => $turno->created_at->format('d/m/Y · H:i'),
                'archivo' => "turno-{$turno->codigo}-{$turno->fecha}.png",
            ];
    @endphp
    @push('scripts')
        <script>
            // El ticket se dibuja en el propio navegador: funciona sin internet y sin librerías.
            const t = @json($imagen);

            document.getElementById('descargar').addEventListener('click', () => {
                const W = 720, P = 56, fuente = 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';
                const c = document.createElement('canvas'), g = c.getContext('2d');

                // [texto, tamaño, negrita, color, espacio antes]; los textos largos se parten en varias líneas.
                const partir = (texto, px, negrita) => {
                    g.font = `${negrita ? 700 : 400} ${px}px ${fuente}`;
                    const lineas = [''];
                    for (const palabra of texto.split(' ')) {
                        const prueba = (lineas.at(-1) + ' ' + palabra).trim();
                        if (g.measureText(prueba).width > W - 2 * P && lineas.at(-1)) lineas.push(palabra);
                        else lineas[lineas.length - 1] = prueba;
                    }
                    return lineas;
                };
                const filas = [];
                const agregar = (texto, px, negrita = false, color = '#0f172a', antes = 14) =>
                    partir(texto, px, negrita).forEach((l, i) => filas.push([l, px, negrita, color, i ? 6 : antes]));

                agregar('GAD Municipal · Catastros y Avalúos', 26, false, '#475569', 0);
                agregar('Su turno', 32, true, '#0f172a', 20);
                agregar(t.codigo, 150, true, '#0f172a', 10);
                if (t.preferente) agregar('Atención preferente', 28, true, '#166534', 10);
                agregar(t.tramite, 32, true, '#0f172a', 18);
                if (t.cedula) agregar('Cédula: ' + t.cedula, 30, false, '#0f172a', 14);
                filas.push(['—', 0, false, '', 30]); // línea punteada
                agregar('Válido solo el', 28, false, '#475569', 30);
                agregar(t.valido, 38, true, '#075985', 8);
                agregar('Emitido el ' + t.emitido, 24, false, '#475569', 24);
                agregar('Espere a que su número aparezca en la pantalla.', 24, false, '#475569', 10);

                c.width = W;
                c.height = P * 2 + filas.reduce((h, [, px, , , antes]) => h + antes + px * 1.15, 0);
                g.fillStyle = '#fff';
                g.fillRect(0, 0, W, c.height);
                g.strokeStyle = '#cbd5e1'; g.lineWidth = 4;
                g.strokeRect(2, 2, W - 4, c.height - 4);
                g.textAlign = 'center'; g.textBaseline = 'top';

                let y = P;
                for (const [texto, px, negrita, color, antes] of filas) {
                    y += antes;
                    if (!px) {
                        g.setLineDash([10, 8]); g.beginPath(); g.moveTo(P, y); g.lineTo(W - P, y); g.stroke(); g.setLineDash([]);
                        continue;
                    }
                    g.font = `${negrita ? 700 : 400} ${px}px ${fuente}`;
                    g.fillStyle = color;
                    g.fillText(texto, W / 2, y);
                    y += px * 1.15;
                }

                c.toBlob(blob => {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = t.archivo;
                    a.click();
                    setTimeout(() => URL.revokeObjectURL(a.href), 10000);
                }, 'image/png');
            });
        </script>
    @endpush
@endif
