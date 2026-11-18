<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantalla de turnos · Catastros y Avalúos</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { margin: 0; background: #0b1220; color: #fff; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; display: grid; grid-template-rows: auto 1fr auto; overflow: hidden; }
        header, footer { display: flex; justify-content: space-between; align-items: center; gap: 2vw; padding: 1.6vh 3vw; background: #0f172a; font-size: clamp(1rem, 2.2vh, 1.6rem); }
        header strong { font-size: 1.3em; }
        #reloj { font-variant-numeric: tabular-nums; font-weight: 700; font-size: 1.5em; }
        main { display: grid; grid-template-columns: 3fr 2fr; gap: 2vw; padding: 2.5vh 3vw; min-height: 0; }
        .ahora { background: #fbbf24; color: #0f172a; border-radius: 2vw; display: grid; place-content: center; text-align: center; padding: 2vh 2vw; }
        .ahora.vacia { background: #1e293b; color: #cbd5e1; }
        .ahora small { font-size: clamp(1.2rem, 4vh, 3rem); font-weight: 600; }
        .ahora .codigo { white-space: nowrap; font-size: clamp(4rem, min(30vh, 16vw), 20rem); font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
        .ahora .ventanilla { font-size: clamp(2rem, 8vh, 6rem); font-weight: 700; }
        .ahora.destello { animation: destello 1s ease-in-out 3; }
        @keyframes destello { 50% { background: #fff; } }
        .lista { display: flex; flex-direction: column; gap: 1.2vh; min-height: 0; }
        .lista h2 { margin: 0; font-size: clamp(1rem, 2.6vh, 1.8rem); font-weight: 600; color: #94a3b8; }
        .fila { flex: 1; max-height: 17vh; display: flex; justify-content: space-between; align-items: center; gap: 1vw; background: #1e293b; border-radius: 1vw; padding: 0 2vw; font-size: clamp(1.4rem, 5.5vh, 4rem); font-weight: 700; font-variant-numeric: tabular-nums; }
        .fila.activo { outline: .4vh solid #fbbf24; }
        .fila span:last-child { font-size: .6em; font-weight: 600; color: #cbd5e1; text-align: right; }
        #sonido { font: inherit; font-weight: 700; padding: .6em 1.2em; border-radius: .6em; border: 0; background: #fbbf24; color: #0f172a; cursor: pointer; }
        #sonido:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
        @media (orientation: portrait) { main { grid-template-columns: 1fr; grid-template-rows: 3fr 2fr; } }
        @media (prefers-reduced-motion: reduce) { .ahora.destello { animation: none; } }
    </style>
</head>
<body>
<header>
    <strong>Catastros y Avalúos · GAD Municipal</strong>
    <span id="reloj"></span>
</header>
<main>
    <section class="ahora vacia" id="ahora" aria-live="assertive">
        <small>Bienvenido</small>
        <div class="ventanilla">Espere su turno</div>
    </section>
    <section class="lista" id="lista" aria-label="Últimos turnos llamados"></section>
</main>
<footer>
    <span>Personas en espera: <strong id="espera">0</strong></span>
    <button id="sonido" type="button">Activar sonido</button>
</footer>

<script>
    const $ = id => document.getElementById(id);
    const esc = s => String(s).replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));
    let vistos = null, conSonido = false;

    // Los navegadores solo permiten audio después de un clic.
    $('sonido').onclick = () => { conSonido = true; $('sonido').remove(); anunciar(null); };

    function anunciar(t) {
        if (!conSonido) return;
        const ctx = new AudioContext();
        [880, 660].forEach((hz, i) => {
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = hz; o.connect(g); g.connect(ctx.destination);
            g.gain.setValueAtTime(.25, ctx.currentTime + i * .35);
            g.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + i * .35 + .6);
            o.start(ctx.currentTime + i * .35); o.stop(ctx.currentTime + i * .35 + .6);
        });
        if (t && 'speechSynthesis' in window) {
            const [letra, numero] = t.codigo.split('-');
            const voz = new SpeechSynthesisUtterance(`Turno ${letra}, ${parseInt(numero)}. ${t.ventanilla}`);
            voz.lang = 'es'; voz.rate = .9;
            setTimeout(() => speechSynthesis.speak(voz), 900);
        }
    }

    async function actualizar() {
        try {
            const r = await fetch('{{ route('pantalla.datos') }}', {headers: {Accept: 'application/json'}});
            if (!r.ok) return;
            const {turnos, en_espera} = await r.json();
            $('espera').textContent = en_espera;

            const actual = turnos.find(t => t.activo);
            $('ahora').className = 'ahora' + (actual ? '' : ' vacia');
            $('ahora').innerHTML = actual
                ? `<small>Turno${actual.preferente ? ' preferente' : ''}</small><div class="codigo">${esc(actual.codigo)}</div><div class="ventanilla">${esc(actual.ventanilla)}</div>`
                : '<small>Bienvenido</small><div class="ventanilla">Espere su turno</div>';
            $('lista').innerHTML = '<h2>Últimos llamados</h2>' + turnos.filter(t => t !== actual).slice(0, 4)
                .map(t => `<div class="fila${t.activo ? ' activo' : ''}"><span>${esc(t.codigo)}</span><span>${esc(t.ventanilla)}</span></div>`).join('');

            // Suena solo con llamadas nuevas (o repetidas), nunca con lo que ya estaba al abrir la pantalla.
            const nuevos = turnos.filter(t => t.activo && vistos && !vistos.has(t.clave));
            vistos = new Set(turnos.map(t => t.clave));
            if (nuevos.length) {
                anunciar(nuevos[0]);
                $('ahora').classList.remove('destello'); void $('ahora').offsetWidth; $('ahora').classList.add('destello');
            }
        } catch {}
    }

    setInterval(() => $('reloj').textContent = new Date().toLocaleTimeString('es-EC', {hour: '2-digit', minute: '2-digit', hour12: false}), 1000);
    setInterval(actualizar, 3000);
    actualizar();
</script>
</body>
</html>
