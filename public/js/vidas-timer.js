(function () {
    var timers = document.querySelectorAll('.cuenta-regresiva-vida[data-segundos]');
    timers.forEach(function (timer) {
        var restante = Math.max(0, parseInt(timer.dataset.segundos, 10) || 0);
        function dibujar() {
            var horas = Math.floor(restante / 3600);
            var minutos = Math.floor((restante % 3600) / 60);
            var segundos = restante % 60;
            timer.textContent = (horas ? String(horas).padStart(2, '0') + ':' : '') +
                String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            if (restante > 0) restante -= 1;
        }
        dibujar();
        window.setInterval(dibujar, 1000);
    });
})();
