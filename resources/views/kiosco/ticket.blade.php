@extends('layouts.app')
@section('titulo', "Turno $turno->codigo")
@section('ancho', 'estrecho')
@if (in_array($turno->estado, ['espera', 'llamado']) && $turno->fecha === today()->toDateString())
    @push('head')<meta http-equiv="refresh" content="10">@endpush {{-- quien lo mira en el celular ve cuándo lo llaman --}}
@endif

@php
    // El ticket muestra solo el número; trámite, cédula y fechas van dentro del QR.
    $qr = $turno->qr();
    // Turno sacado para otro día: la fecha va visible (también en papel) para quien no escanee el QR.
    $diaReservado = $turno->fecha !== $turno->created_at->toDateString()
        ? \Illuminate\Support\Carbon::parse($turno->fecha)->translatedFormat('l j \d\e F') : null;
    $lado = count($qr) + 8; // 4 módulos de margen blanco a cada lado, como pide el estándar
    $trazo = '';
    foreach ($qr as $y => $fila) {
        foreach ($fila as $x => $oscuro) {
            if ($oscuro) {
                $trazo .= 'M'.($x + 4).' '.($y + 4).'h1v1h-1z';
            }
        }
    }
@endphp

@section('contenido')
    <div class="tarjeta ticket">
        <p class="suave">GAD Municipal · Catastros y Avalúos</p>
        <p><strong>Su turno</strong></p>
        <div class="codigo">{{ $turno->codigo }}</div>
        @if ($diaReservado)
            <p class="fecha-turno">Válido solo el<br><strong>{{ $diaReservado }}</strong></p>
        @endif
        <svg class="qr" viewBox="0 0 {{ $lado }} {{ $lado }}" role="img" aria-labelledby="qr-datos" shape-rendering="crispEdges">
            <rect width="100%" height="100%" fill="#fff"/>
            <path d="{{ $trazo }}" fill="#000"/>
        </svg>
        <p class="solo-lector" id="qr-datos">Código QR con los datos del turno: {{ str_replace("\n", '. ', $turno->textoQr()) }}</p>
        <p class="suave">Escanee el código para ver los datos del turno.</p>

        <div class="no-imprimir">
            <hr>
            @if ($turno->esFuturo())
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
        </div>

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
    @php $imagen = ['codigo' => $turno->codigo, 'dia' => $diaReservado, 'qr' => $qr, 'archivo' => "turno-{$turno->codigo}-{$turno->fecha}.png"]; @endphp
    @push('scripts')
        <script>
            // La imagen se dibuja en el propio navegador, igual que el papel: número y QR.
            const t = @json($imagen);

            document.getElementById('descargar').addEventListener('click', () => {
                const W = 720, fuente = 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';
                const modulo = Math.floor(440 / (t.qr.length + 8)), ladoQr = modulo * (t.qr.length + 8);
                const c = document.createElement('canvas'), g = c.getContext('2d');
                c.width = W;
                const extra = t.dia ? 90 : 0; // espacio para la fecha en turnos de otro día
                c.height = 340 + extra + ladoQr + 110;

                g.fillStyle = '#fff';
                g.fillRect(0, 0, W, c.height);
                g.strokeStyle = '#cbd5e1'; g.lineWidth = 4;
                g.strokeRect(2, 2, W - 4, c.height - 4);
                g.textAlign = 'center'; g.textBaseline = 'top';

                const texto = (s, px, peso, color, y) => { g.font = `${peso} ${px}px ${fuente}`; g.fillStyle = color; g.fillText(s, W / 2, y); };
                texto('GAD Municipal · Catastros y Avalúos', 26, 400, '#475569', 56);
                texto('Su turno', 32, 700, '#0f172a', 100);
                texto(t.codigo, 150, 700, '#0f172a', 150);
                if (t.dia) {
                    texto('Válido solo el', 26, 400, '#475569', 320);
                    texto(t.dia, 36, 700, '#075985', 354);
                }

                const x0 = (W - ladoQr) / 2 + 4 * modulo, y0 = 330 + extra + 4 * modulo;
                g.fillStyle = '#000';
                t.qr.forEach((fila, y) => fila.forEach((oscuro, x) => oscuro && g.fillRect(x0 + x * modulo, y0 + y * modulo, modulo, modulo)));

                texto('Escanee el código para ver los datos del turno.', 24, 400, '#475569', 340 + extra + ladoQr + 30);

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
