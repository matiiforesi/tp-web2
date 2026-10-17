(function () {
    var selPais = document.getElementById('pais_id');
    var selCiudad = document.getElementById('ciudad_id');
    var contenedorMapa = document.getElementById('mapa-contenedor');
    var iframe = document.getElementById('mapa-iframe');
    var enlace = document.getElementById('mapa-enlace');
    var inputFoto = document.getElementById('foto_perfil');
    var preview = document.getElementById('foto-preview');
    var ciudades = JSON.parse(document.getElementById('ciudades-data').textContent);

    function cargarCiudades() {
        var paisId = selPais.value;
        selCiudad.innerHTML = '';
        var vacia = document.createElement('option');
        vacia.value = '';
        vacia.textContent = paisId ? 'Elegí una ciudad' : 'Elegí un país primero';
        selCiudad.appendChild(vacia);

        ciudades.forEach(function (c) {
            if (String(c.pais_id) === paisId) {
                var op = document.createElement('option');
                op.value = c.id;
                op.textContent = c.nombre;
                selCiudad.appendChild(op);
            }
        });
        mostrarMapa();
    }

    // Vista previa con el mapa embebido de OpenStreetMap (sin librerias).
    function mostrarMapa() {
        var ciudad = ciudades.filter(function (c) {
            return String(c.id) === selCiudad.value;
        })[0];
        if (!ciudad) {
            contenedorMapa.hidden = true;
            iframe.removeAttribute('src');
            return;
        }
        var lat = parseFloat(ciudad.latitud);
        var lon = parseFloat(ciudad.longitud);
        var bbox = [lon - 0.08, lat - 0.05, lon + 0.08, lat + 0.05].join('%2C');

        iframe.src = 'https://www.openstreetmap.org/export/embed.html?bbox=' + bbox +
            '&layer=mapnik&marker=' + lat + '%2C' + lon;
        enlace.href = 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lon +
            '#map=12/' + lat + '/' + lon;
        contenedorMapa.hidden = false;
    }

    selPais.addEventListener('change', cargarCiudades);
    selCiudad.addEventListener('change', mostrarMapa);

    inputFoto.addEventListener('change', function () {
        var archivo = inputFoto.files[0];
        if (!archivo) {
            preview.hidden = true;
            return;
        }
        preview.src = URL.createObjectURL(archivo);
        preview.hidden = false;
    });

    // Si el servidor devolvio el formulario con errores, la ciudad ya viene elegida.
    mostrarMapa();
})();
