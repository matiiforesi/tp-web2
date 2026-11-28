(function () {
    'use strict';

    var contenedor = document.getElementById('perfil-mapa');
    if (!contenedor) return;

    var lat = parseFloat(contenedor.dataset.lat);
    var lng = parseFloat(contenedor.dataset.lng);
    if (isNaN(lat) || isNaN(lng)) {
        contenedor.hidden = true;
        return;
    }

    if (typeof L === 'undefined') {
        contenedor.classList.add('perfil-mapa-error');
        contenedor.textContent = 'No se pudo cargar el mapa.';
        return;
    }

    var mapa = L.map(contenedor, {scrollWheelZoom: false}).setView([lat, lng], 11);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapa);

    var marcador = L.marker([lat, lng]).addTo(mapa);
    var lugar = contenedor.dataset.lugar;
    if (lugar) marcador.bindPopup(lugar).openPopup();
})();
