(function () {
    'use strict';

    var modal = document.getElementById('modal-qr');
    var abrir = document.getElementById('abrir-qr');
    var cerrar = document.getElementById('cerrar-qr');
    var destino = document.getElementById('qr-imagen');
    if (!modal || !abrir || !cerrar || !destino) return;

    function cerrarModal() {
        modal.hidden = true;
        abrir.focus();
    }

    abrir.addEventListener('click', function () {
        if (typeof window.qrcode !== 'function') return;
        destino.replaceChildren();
        var codigo = window.qrcode(0, 'M');
        codigo.addData(window.perfilPublicoUrl);
        codigo.make();
        destino.innerHTML = codigo.createSvgTag(6, 8);
        modal.hidden = false;
        cerrar.focus();
    });

    cerrar.addEventListener('click', cerrarModal);
    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) cerrarModal();
    });
    modal.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') cerrarModal();
    });
})();
