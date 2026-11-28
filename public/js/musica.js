(function () {
    var CLAVE = 'musica';
    var TEMPO = 84;
    var NEGRA = 60 / TEMPO;
    var ADELANTO = 0.25;
    var VOLUMEN_MUSICA = 0.16;

    var ACORDES = [
        {bajo: [38, 45, 41, 45], notas: [50, 53, 57, 60]},
        {bajo: [43, 50, 47, 50], notas: [47, 53, 55, 59]},
        {bajo: [36, 43, 40, 43], notas: [48, 52, 55, 59]},
        {bajo: [45, 52, 49, 52], notas: [49, 52, 55, 57]}
    ];
    var MELODIA = [
        [[0, 69], [1.5, 72], [2.5, 74], [3, 72]],
        [[0, 71], [1, 67], [2.5, 65], [3.5, 67]],
        [[0, 64], [1.5, 67], [2, 71], [3, 72]],
        [[0, 73], [1, 69], [2, 67], [3, 64]]
    ];

    var ctx = null, maestro = null, musica = null, efectos = null;
    var sonando = false, proximoCompas = 0, compas = 0, temporizador = null;

    function leerPreferencia() {
        try { return localStorage.getItem(CLAVE) !== 'off'; } catch (e) { return true; }
    }

    function guardarPreferencia(encendida) {
        try { localStorage.setItem(CLAVE, encendida ? 'on' : 'off'); } catch (e) {}
    }

    var activada = leerPreferencia();

    function frecuencia(midi) {
        return 440 * Math.pow(2, (midi - 69) / 12);
    }

    function iniciarContexto() {
        if (ctx) return true;
        var Contexto = window.AudioContext || window.webkitAudioContext;
        if (!Contexto) return false;
        ctx = new Contexto();
        maestro = ctx.createGain();
        maestro.gain.value = 1;
        maestro.connect(ctx.destination);
        musica = ctx.createGain();
        musica.gain.value = 0;
        musica.connect(maestro);
        efectos = ctx.createGain();
        efectos.gain.value = 0.35;
        efectos.connect(maestro);
        return true;
    }

    function nota(destino, midi, inicio, duracion, volumen, tipo, filtroHz) {
        var osc = ctx.createOscillator();
        var env = ctx.createGain();
        osc.type = tipo || 'sine';
        osc.frequency.value = frecuencia(midi);
        var salida = env;
        if (filtroHz) {
            var filtro = ctx.createBiquadFilter();
            filtro.type = 'lowpass';
            filtro.frequency.value = filtroHz;
            env.connect(filtro);
            salida = filtro;
        }
        env.gain.setValueAtTime(0.0001, inicio);
        env.gain.exponentialRampToValueAtTime(volumen, inicio + 0.02);
        env.gain.exponentialRampToValueAtTime(0.0001, inicio + duracion);
        osc.connect(env);
        salida.connect(destino);
        osc.start(inicio);
        osc.stop(inicio + duracion + 0.05);
    }

    function escobilla(inicio, volumen) {
        var largo = Math.floor(ctx.sampleRate * 0.08);
        var buffer = ctx.createBuffer(1, largo, ctx.sampleRate);
        var datos = buffer.getChannelData(0);
        for (var i = 0; i < largo; i++) datos[i] = (Math.random() * 2 - 1) * (1 - i / largo);
        var fuente = ctx.createBufferSource();
        fuente.buffer = buffer;
        var filtro = ctx.createBiquadFilter();
        filtro.type = 'highpass';
        filtro.frequency.value = 6000;
        var env = ctx.createGain();
        env.gain.value = volumen;
        fuente.connect(filtro);
        filtro.connect(env);
        env.connect(musica);
        fuente.start(inicio);
    }

    function programarCompas(inicio, numero) {
        var acorde = ACORDES[numero % ACORDES.length];
        [0, 1.66, 2].forEach(function (tiempo, i) {
            acorde.notas.forEach(function (midi) {
                nota(musica, midi, inicio + tiempo * NEGRA, i === 1 ? NEGRA * 0.6 : NEGRA * 1.6, 0.05, 'triangle', 1800);
            });
        });
        acorde.bajo.forEach(function (midi, i) {
            nota(musica, midi, inicio + i * NEGRA, NEGRA * 0.9, 0.22, 'sine');
        });
        if (Math.floor(numero / ACORDES.length) % 2 === 1) {
            MELODIA[numero % MELODIA.length].forEach(function (par) {
                nota(musica, par[1], inicio + par[0] * NEGRA, NEGRA * 0.9, 0.07, 'sine');
            });
        }
        for (var t = 0; t < 4; t++) {
            escobilla(inicio + t * NEGRA, 0.05);
            escobilla(inicio + (t + 0.66) * NEGRA, 0.025);
        }
    }

    function programador() {
        while (proximoCompas < ctx.currentTime + ADELANTO) {
            programarCompas(proximoCompas, compas);
            proximoCompas += 4 * NEGRA;
            compas++;
        }
    }

    function arrancar() {
        if (sonando || !activada || !iniciarContexto()) return;
        if (ctx.state === 'suspended') ctx.resume();
        sonando = true;
        proximoCompas = ctx.currentTime + 0.1;
        musica.gain.cancelScheduledValues(ctx.currentTime);
        musica.gain.setValueAtTime(musica.gain.value, ctx.currentTime);
        musica.gain.linearRampToValueAtTime(VOLUMEN_MUSICA, ctx.currentTime + 1.5);
        programador();
        temporizador = setInterval(programador, 100);
    }

    function detener() {
        if (!sonando) return;
        sonando = false;
        clearInterval(temporizador);
        musica.gain.cancelScheduledValues(ctx.currentTime);
        musica.gain.setValueAtTime(musica.gain.value, ctx.currentTime);
        musica.gain.linearRampToValueAtTime(0, ctx.currentTime + 0.4);
    }


    function efectosDisponibles() {
        if (!activada || !iniciarContexto()) return false;
        if (ctx.state === 'suspended') ctx.resume();
        return true;
    }

    function giroRuleta(duracionMs) {
        if (!efectosDisponibles()) return;
        var ahora = ctx.currentTime;
        var total = duracionMs / 1000;
        var clics = 42;
        for (var i = 0; i < clics; i++) {
            var avance = i / clics;
            var t = total * Math.pow(avance, 2.2);
            nota(efectos, 84, ahora + t, 0.04, 0.25, 'square', 3000);
        }
    }

    function premio() {
        if (!efectosDisponibles()) return;
        var ahora = ctx.currentTime;
        [72, 76, 79, 84].forEach(function (midi, i) {
            nota(efectos, midi, ahora + i * 0.09, 0.5, 0.25, 'triangle');
        });
    }

    window.Musica = {giroRuleta: giroRuleta, premio: premio};


    var boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'boton-musica';

    function pintarBoton() {
        boton.textContent = activada ? '🔊' : '🔇';
        boton.setAttribute('aria-pressed', activada ? 'true' : 'false');
        boton.setAttribute('aria-label', activada ? 'Apagar música' : 'Prender música');
        boton.title = activada ? 'Apagar música' : 'Prender música';
    }

    boton.addEventListener('click', function (evento) {
        evento.stopPropagation();
        activada = !activada;
        guardarPreferencia(activada);
        pintarBoton();
        if (activada) arrancar(); else detener();
    });

    pintarBoton();
    document.body.appendChild(boton);

    function primerGesto() {
        arrancar();
        if (sonando) {
            document.removeEventListener('pointerdown', primerGesto);
            document.removeEventListener('keydown', primerGesto);
        }
    }
    document.addEventListener('pointerdown', primerGesto);
    document.addEventListener('keydown', primerGesto);
})();
