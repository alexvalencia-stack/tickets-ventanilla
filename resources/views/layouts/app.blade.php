<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Turnos') · Catastros y Avalúos</title>
    @stack('head')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<a class="salto" href="#contenido">Saltar al contenido</a>
<header class="cabecera">
    <div class="cabecera-in">
        <a class="marca" href="{{ route('kiosco') }}">
            <span class="marca-logo" aria-hidden="true">
                <svg class="icono" viewBox="0 0 24 24"><path d="M3 21h18M5 21V10l7-6 7 6v11M9 21v-6h6v6"/></svg>
            </span>
            <span>Catastros y Avalúos<small>Sistema de turnos · GAD Municipal</small></span>
        </a>
        @auth
            <nav class="nav" aria-label="Principal">
                <a href="{{ route('ventanilla') }}" @if(request()->routeIs('ventanilla')) aria-current="page" @endif>Mi ventanilla</a>
                @can('admin')
                    <a href="{{ route('admin.inicio') }}" @if(request()->routeIs('admin.inicio')) aria-current="page" @endif>Resumen</a>
                    <a href="{{ route('admin.tramites') }}" @if(request()->routeIs('admin.tramites*')) aria-current="page" @endif>Trámites</a>
                    <a href="{{ route('admin.ventanillas') }}" @if(request()->routeIs('admin.ventanillas*')) aria-current="page" @endif>Ventanillas</a>
                    <a href="{{ route('admin.usuarios') }}" @if(request()->routeIs('admin.usuarios*')) aria-current="page" @endif>Usuarios</a>
                @endcan
                <a href="{{ route('pantalla') }}" target="_blank" rel="noopener">Pantalla</a>
                <form method="post" action="{{ route('logout') }}">@csrf<button>Salir ({{ auth()->user()->name }})</button></form>
            </nav>
        @endauth
    </div>
</header>
<main id="contenido" class="contenido @yield('ancho')">
    @if (session('aviso'))
        <div class="alerta exito" role="status">{{ session('aviso') }}</div>
    @endif
    @if ($errors->any())
        <div class="alerta error" role="alert">
            <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif
    @yield('contenido')
</main>
<script>
    // Evita el doble clic en formularios (dos turnos, dos llamadas).
    document.addEventListener('submit', e => {
        if (e.defaultPrevented) return;
        e.target.querySelectorAll('button').forEach(b => setTimeout(() => b.disabled = true));
    });

    // Modo kiosco: tras un minuto sin que nadie toque la pantalla vuelve al inicio, para que el
    // siguiente ciudadano no vea el ticket (ni la cédula) del anterior. Se activa abriendo /?kiosco=1
    // en el equipo del kiosco y se quita con /?kiosco=0; en los celulares nunca se activa.
    (() => {
        const guardar = (almacen, clave, valor) => { try { valor === null ? almacen.removeItem(clave) : almacen.setItem(clave, valor); } catch {} };
        const leer = (almacen, clave) => { try { return almacen.getItem(clave); } catch { return null; } };
        const modo = new URLSearchParams(location.search).get('kiosco');
        if (modo !== null) guardar(localStorage, 'kiosco', modo === '1' ? '1' : null);
        if (leer(localStorage, 'kiosco') !== '1') return;

        const LIMITE = 60, AVISO = 15;
        if (location.pathname === '/') { guardar(sessionStorage, 'actividad', null); return; }

        // La hora de la última actividad sobrevive a las recargas automáticas del ticket (cada 10 s).
        const tocar = () => guardar(sessionStorage, 'actividad', String(Date.now()));
        if (!leer(sessionStorage, 'actividad')) tocar();
        ['pointerdown', 'keydown', 'input'].forEach(ev => document.addEventListener(ev, tocar, {passive: true}));

        const aviso = document.createElement('div');
        aviso.className = 'aviso-inactividad';
        aviso.setAttribute('role', 'status');
        aviso.hidden = true;
        document.body.append(aviso);

        setInterval(() => {
            const quedan = LIMITE - Math.floor((Date.now() - Number(leer(sessionStorage, 'actividad'))) / 1000);
            if (quedan <= 0) { guardar(sessionStorage, 'actividad', null); location.href = '/'; return; }
            aviso.hidden = quedan > AVISO;
            aviso.textContent = `Por inactividad volverá al inicio en ${quedan} s. Toque la pantalla para continuar.`;
        }, 1000);
    })();
</script>
@stack('scripts')
</body>
</html>
