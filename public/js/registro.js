(function () {
    'use strict';

    var inputFoto = document.getElementById('foto_perfil');
    var preview = document.getElementById('foto-preview');
    var form = document.getElementById('form-registro');
    var contenedor = document.getElementById('mapa');
    var texto = document.getElementById('ubicacion-texto');

    var campos = {
        pais_codigo: document.getElementById('pais_codigo'),
        pais: document.getElementById('pais'),
        ciudad: document.getElementById('ciudad'),
        latitud: document.getElementById('latitud'),
        longitud: document.getElementById('longitud')
    };

    // ── Foto de perfil ──────────────────────────────────
    // El hosting bloquea los envios multipart/form-data (403). Por eso la foto se reduce y
    // se comprime aca y viaja como texto base64 en un campo oculto. El servidor la valida igual.
    var campoFoto = document.getElementById('foto_base64');
    var mensajeFoto = document.getElementById('foto-mensaje');
    var LADO_MAXIMO = 512;
    var BYTES_MAXIMOS_ORIGINAL = 15 * 1024 * 1024;
    var CHARS_MAXIMOS = 700000; // ~500 KB de imagen: muy por debajo de lo que acepta el servidor

    function mostrarFoto(mensaje, esError) {
        mensajeFoto.textContent = mensaje;
        mensajeFoto.classList.toggle('ubicacion-error', !!esError);
    }

    function limpiarFoto() {
        campoFoto.value = '';
        preview.hidden = true;
    }

    function comprimir(imagen, ladoMaximo) {
        var escala = Math.min(1, ladoMaximo / Math.max(imagen.width, imagen.height));
        var lienzo = document.createElement('canvas');
        lienzo.width = Math.max(1, Math.round(imagen.width * escala));
        lienzo.height = Math.max(1, Math.round(imagen.height * escala));

        var ctx = lienzo.getContext('2d');
        ctx.fillStyle = '#ffffff'; // JPEG no tiene transparencia
        ctx.fillRect(0, 0, lienzo.width, lienzo.height);
        ctx.drawImage(imagen, 0, 0, lienzo.width, lienzo.height);

        var calidades = [0.85, 0.7, 0.55];
        for (var i = 0; i < calidades.length; i++) {
            var datos = lienzo.toDataURL('image/jpeg', calidades[i]);
            if (datos.length <= CHARS_MAXIMOS) {
                return datos;
            }
        }
        return null;
    }

    inputFoto.addEventListener('change', function () {
        limpiarFoto();
        var archivo = inputFoto.files[0];
        if (!archivo) {
            mostrarFoto('', false);
            return;
        }
        if (archivo.size > BYTES_MAXIMOS_ORIGINAL) {
            mostrarFoto('La foto es demasiado grande. Elegí una de menos de 15 MB.', true);
            return;
        }

        mostrarFoto('Procesando foto…', false);
        var url = URL.createObjectURL(archivo);
        var imagen = new Image();

        imagen.onload = function () {
            URL.revokeObjectURL(url);
            var resultado = comprimir(imagen, LADO_MAXIMO) || comprimir(imagen, 320);
            if (!resultado) {
                mostrarFoto('No pudimos reducir la foto. Probá con otra.', true);
                return;
            }
            campoFoto.value = resultado;
            preview.src = resultado;
            preview.hidden = false;
            mostrarFoto('Foto lista.', false);
        };
        imagen.onerror = function () {
            URL.revokeObjectURL(url);
            mostrarFoto('No pudimos leer ese archivo como imagen. Probá con un JPG, PNG o WEBP.', true);
        };
        imagen.src = url;
    });

    // ── Mapa (Leaflet + OpenStreetMap) ──────────────────
    function mostrar(mensaje, esError) {
        texto.textContent = mensaje;
        texto.classList.toggle('ubicacion-error', !!esError);
    }

    function limpiarUbicacion() {
        Object.keys(campos).forEach(function (k) {
            campos[k].value = '';
        });
    }

    form.addEventListener('submit', function (evento) {
        if (!campoFoto.value) {
            evento.preventDefault();
            mostrarFoto('Elegí una foto de perfil.', true);
            inputFoto.scrollIntoView({behavior: 'smooth', block: 'center'});
            return;
        }
        if (!campos.latitud.value || !campos.longitud.value) {
            evento.preventDefault();
            mostrar('Elegí tu ubicación haciendo click en el mapa.', true);
            contenedor.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    });

    if (typeof L === 'undefined') {
        mostrar('No se pudo cargar el mapa. Revisá tu conexión y recargá la página.', true);
        return;
    }

    var mapa = L.map(contenedor, {worldCopyJump: true}).setView([-38, -63], 4);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapa);

    var marcador = null;
    var temporizador = null;
    var controlador = null;
    var secuencia = 0;

    function ponerMarcador(latlng) {
        if (marcador === null) {
            marcador = L.marker(latlng, {draggable: true}).addTo(mapa);
            marcador.on('dragend', function () {
                elegirPunto(marcador.getLatLng());
            });
        } else {
            marcador.setLatLng(latlng);
        }
    }

    function elegirPunto(latlng) {
        latlng = latlng.wrap();
        ponerMarcador(latlng);
        limpiarUbicacion();
        mostrar('Buscando ubicación…', false);

        clearTimeout(temporizador);
        temporizador = setTimeout(function () {
            buscar(latlng);
        }, 500);
    }

    function buscar(latlng) {
        if (controlador) {
            controlador.abort();
        }
        controlador = new AbortController();
        var miBusqueda = ++secuencia;

        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=10&addressdetails=1' +
            '&accept-language=es&lat=' + latlng.lat.toFixed(6) + '&lon=' + latlng.lng.toFixed(6);

        fetch(url, {signal: controlador.signal, headers: {'Accept': 'application/json'}})
            .then(function (respuesta) {
                if (!respuesta.ok) {
                    throw new Error('HTTP ' + respuesta.status);
                }
                return respuesta.json();
            })
            .then(function (datos) {
                if (miBusqueda !== secuencia) {
                    // Ya se eligio otro punto
                    return;
                }

                var a = datos.address || {};
                var pais = a.country;
                var codigo = (a.country_code || '').toUpperCase();
                // Si el punto no cae en una localidad, se usa la division administrativa mas cercana.
                var ciudad = a.city || a.town || a.village || a.municipality ||
                    a.county || a.state_district || a.state;

                if (!pais || !codigo || !ciudad) {
                    mostrar('Ese punto no pertenece a ningún país o ciudad. Elegí otro.', true);
                    return;
                }

                campos.pais_codigo.value = codigo;
                campos.pais.value = pais;
                campos.ciudad.value = ciudad;
                campos.latitud.value = latlng.lat.toFixed(6);
                campos.longitud.value = latlng.lng.toFixed(6);
                mostrar('Ubicación elegida: ' + ciudad + ', ' + pais, false);
            })
            .catch(function (error) {
                if (error.name === 'AbortError' || miBusqueda !== secuencia) {
                    return;
                }
                mostrar('No pudimos obtener la ubicación. Probá de nuevo.', true);
            });
    }

    mapa.on('click', function (evento) {
        elegirPunto(evento.latlng);
    });

    // Si el servidor devolvio el formulario con errores, se recupera el punto elegido.
    var latPrevia = parseFloat(campos.latitud.value);
    var lngPrevia = parseFloat(campos.longitud.value);
    if (!isNaN(latPrevia) && !isNaN(lngPrevia)) {
        var previo = L.latLng(latPrevia, lngPrevia);
        ponerMarcador(previo);
        mapa.setView(previo, 10);
        mostrar('Ubicación elegida: ' + campos.ciudad.value + ', ' + campos.pais.value, false);
    }
})();
