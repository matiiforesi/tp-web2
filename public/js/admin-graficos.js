(function () {
    'use strict';

    var boton = document.getElementById('imprimir');
    if (boton) {
        boton.addEventListener('click', function () {
            window.print();
        });
    }

    var aviso = document.getElementById('aviso-sin-chart');
    var datosNodo = document.getElementById('datos-graficos');
    if (!datosNodo) {
        return;
    }

    // Si Chart.js no cargó, la página sigue siendo útil: quedan las tablas.
    if (typeof window.Chart === 'undefined') {
        document.querySelectorAll('.admin-grafico').forEach(function (nodo) {
            nodo.hidden = true;
        });
        if (aviso) {
            aviso.hidden = false;
        }
        return;
    }

    var datos;
    try {
        datos = JSON.parse(datosNodo.textContent);
    } catch (e) {
        return;
    }

    var COLORES = ['#e4b93f', '#b12b42', '#356a9a', '#49a49c', '#8e5fb5', '#d7752b', '#6aa84f', '#c9d3e3', '#7f8ea8'];
    var TEMA_PANTALLA = {texto: '#f7f1df', grilla: 'rgba(212,175,55,0.2)'};
    var TEMA_IMPRESION = {texto: '#111111', grilla: 'rgba(0,0,0,0.2)'};

    Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", sans-serif';
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;

    var graficos = [];

    function crear(id, config) {
        var canvas = document.getElementById(id);
        if (!canvas) {
            return;
        }
        graficos.push(new Chart(canvas, config));
    }

    function colores(n) {
        var lista = [];
        for (var i = 0; i < n; i++) {
            lista.push(COLORES[i % COLORES.length]);
        }
        return lista;
    }

    // Barras horizontales: legibles en pantallas angostas.
    crear('grafico-aciertos', {
        type: 'bar',
        data: {
            labels: datos.aciertos.labels,
            datasets: [{label: '% de aciertos', data: datos.aciertos.valores, backgroundColor: '#e4b93f'}]
        },
        options: {
            indexAxis: 'y',
            plugins: {
                legend: {display: false}, tooltip: {
                    callbacks: {
                        label: function (c) {
                            return c.parsed.x + ' %';
                        }
                    }
                }
            },
            scales: {
                x: {
                    min: 0, max: 100, ticks: {
                        callback: function (v) {
                            return v + '%';
                        }
                    }
                }
            }
        }
    });

    function dona(id, grafico, tipo) {
        crear(id, {
            type: tipo,
            data: {
                labels: grafico.labels,
                datasets: [{data: grafico.valores, backgroundColor: colores(grafico.valores.length), borderWidth: 1}]
            },
            options: {plugins: {legend: {position: 'bottom'}}}
        });
    }

    dona('grafico-paises', datos.paises, 'doughnut');
    dona('grafico-sexo', datos.sexo, 'pie');

    crear('grafico-edad', {
        type: 'bar',
        data: {
            labels: datos.edad.labels,
            datasets: [{
                label: 'Usuarios',
                data: datos.edad.valores,
                backgroundColor: colores(datos.edad.valores.length)
            }]
        },
        options: {
            plugins: {legend: {display: false}},
            scales: {y: {beginAtZero: true, ticks: {precision: 0}}}
        }
    });

    // Aciertos por categoría.
    crear('grafico-categorias', {
        type: 'bar',
        data: {
            labels: datos.categorias.labels,
            datasets: [{
                label: '% de aciertos',
                data: datos.categorias.valores,
                backgroundColor: colores(datos.categorias.valores.length)
            }]
        },
        options: {
            plugins: {
                legend: {display: false}, tooltip: {
                    callbacks: {
                        label: function (c) {
                            return c.parsed.y + ' %';
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 0, max: 100, ticks: {
                        callback: function (v) {
                            return v + '%';
                        }
                    }
                }
            }
        }
    });

    // Partidas en el tiempo, con selector Día / Semana / Mes.
    var serie = null;
    var canvasSerie = document.getElementById('grafico-serie');
    if (canvasSerie && datos.series) {
        serie = new Chart(canvasSerie, {
            type: 'bar',
            data: {
                labels: datos.series.dia.labels,
                datasets: [{label: 'Partidas', data: datos.series.dia.valores, backgroundColor: '#e4b93f'}]
            },
            options: {
                plugins: {legend: {display: false}},
                scales: {y: {beginAtZero: true, ticks: {precision: 0}}, x: {ticks: {maxRotation: 60, autoSkip: true}}}
            }
        });
        graficos.push(serie);
    }

    var botonesGran = document.querySelectorAll('.admin-gran');
    var tablasSerie = document.querySelectorAll('.admin-serie-tabla');

    function elegirGranularidad(g) {
        botonesGran.forEach(function (b) {
            var activo = b.getAttribute('data-g') === g;
            b.classList.toggle('admin-gran-activo', activo);
            b.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });
        // Sin JS se ven las tres tablas; con JS, solo la elegida (al imprimir se muestran todas).
        tablasSerie.forEach(function (t) {
            t.hidden = t.getAttribute('data-g') !== g;
        });
        if (serie && datos.series[g]) {
            serie.data.labels = datos.series[g].labels;
            serie.data.datasets[0].data = datos.series[g].valores;
            serie.update();
        }
    }

    botonesGran.forEach(function (b) {
        b.addEventListener('click', function () {
            elegirGranularidad(b.getAttribute('data-g'));
        });
    });
    if (botonesGran.length) {
        elegirGranularidad('dia');
    }

    // Colores de texto según el medio (el fondo de la pantalla es oscuro y el papel es blanco).
    function aplicarTema(tema) {
        graficos.forEach(function (g) {
            var o = g.options;
            if (o.plugins && o.plugins.legend && o.plugins.legend.labels) {
                o.plugins.legend.labels.color = tema.texto;
            } else if (o.plugins && o.plugins.legend) {
                o.plugins.legend.labels = {color: tema.texto};
            }
            if (o.scales) {
                Object.keys(o.scales).forEach(function (eje) {
                    var e = o.scales[eje];
                    e.ticks = e.ticks || {};
                    e.ticks.color = tema.texto;
                    e.grid = e.grid || {};
                    e.grid.color = tema.grilla;
                });
            }
            g.update('none');
        });
    }

    aplicarTema(TEMA_PANTALLA);

    window.addEventListener('beforeprint', function () {
        aplicarTema(TEMA_IMPRESION);
    });
    window.addEventListener('afterprint', function () {
        aplicarTema(TEMA_PANTALLA);
    });
    if (window.matchMedia) {
        var mql = window.matchMedia('print');
        var cambio = function (e) {
            aplicarTema(e.matches ? TEMA_IMPRESION : TEMA_PANTALLA);
        };
        if (mql.addEventListener) {
            mql.addEventListener('change', cambio);
        } else if (mql.addListener) {
            mql.addListener(cambio);
        }
    }
})();
