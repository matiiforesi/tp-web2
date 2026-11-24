<?php

namespace app\controller;

use app\model\AdminModel;

class AdminController
{
    const ROL_ADMINISTRADOR = 3;
    const BARRAS_TOP = 10;      // Jugadores en el grafico de aciertos
    const PAISES_TOP = 8;       // Paises que se muestran en el grafico; el resto se agrupa en "Otros"
    const LARGO_ENUNCIADO = 90;

    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    // GET /admin/panel
    public function panel()
    {
        $this->exigirAdministrador();

        $periodo = AdminModel::periodoValido(\Request::get('periodo'));
        $metricas = $this->model->metricas($periodo);

        $aciertos = $this->model->aciertosPorUsuario($periodo, 100);
        $categorias = $this->model->aciertosPorCategoria($periodo);
        $dificultad = $this->model->dificultadPreguntas($periodo);
        $series = $this->model->partidasPorTiempo($periodo);
        $paises = $this->model->usuariosPorPais($periodo);
        $sexo = $this->model->usuariosPorSexo($periodo);
        $edad = $this->model->usuariosPorEdad($periodo);

        $totalUsuarios = $metricas['usuariosNuevos'];

        $tablaAciertos = [];
        foreach ($aciertos as $i => $fila) {
            $tablaAciertos[] = [
                'posicion' => $i + 1,
                'usuario' => $fila['usuario'],
                'pais' => $fila['pais'],
                'correctas' => $fila['correctas'],
                'respondidas' => $fila['respondidas'],
                'porcentaje' => $this->formatearPorcentaje($fila['porcentaje']),
            ];
        }

        $tablaCategorias = [];
        foreach ($categorias as $fila) {
            $tablaCategorias[] = [
                'categoria' => $fila['categoria'],
                'correctas' => $fila['correctas'],
                'respondidas' => $fila['respondidas'],
                'porcentaje' => $fila['porcentaje'] === null ? '—' : $this->formatearPorcentaje($fila['porcentaje']) . ' %',
            ];
        }

        $tablaPaises = [];
        foreach ($paises as $fila) {
            $tablaPaises[] = [
                'pais' => $fila['pais'],
                'total' => (int)$fila['total'],
                'porcentaje' => $this->porcentajeDe((int)$fila['total'], $totalUsuarios),
            ];
        }

        $tablaSexo = [];
        foreach (AdminModel::ETIQUETAS_SEXO as $clave => $etiqueta) {
            $tablaSexo[] = [
                'etiqueta' => $etiqueta,
                'total' => $sexo[$clave],
                'porcentaje' => $this->porcentajeDe($sexo[$clave], $totalUsuarios),
            ];
        }

        $tablaEdad = [];
        foreach (AdminModel::ETIQUETAS_EDAD as $clave => $etiqueta) {
            $tablaEdad[] = [
                'etiqueta' => $etiqueta,
                'total' => $edad[$clave],
                'porcentaje' => $this->porcentajeDe($edad[$clave], $totalUsuarios),
            ];
        }

        $estados = [];
        foreach (AdminModel::ETIQUETAS_ESTADO_PREGUNTA as $clave => $etiqueta) {
            $estados[] = ['etiqueta' => $etiqueta, 'total' => $metricas['preguntasPorEstado'][$clave]];
        }

        $opcionesPeriodo = [];
        foreach (AdminModel::PERIODOS as $clave => $datos) {
            $opcionesPeriodo[] = [
                'clave' => (string)$clave,
                'etiqueta' => $datos['etiqueta'],
                'activo' => (string)$clave === $periodo,
            ];
        }

        $m = $metricas;
        $m['promedioPreguntasPorPartida'] = $this->formatearNumero($m['promedioPreguntasPorPartida']);
        $m['aciertoGlobal'] = $m['aciertoGlobal'] === null ? '—' : $this->formatearPorcentaje($m['aciertoGlobal']) . ' %';

        // Datos para Chart.js. Se arman aca en el servidor y el JS solo los dibuja.
        $graficos = [
            'aciertos' => [
                'labels' => array_column(array_slice($aciertos, 0, self::BARRAS_TOP), 'usuario'),
                'valores' => array_column(array_slice($aciertos, 0, self::BARRAS_TOP), 'porcentaje'),
            ],
            'categorias' => [
                'labels' => array_column($categorias, 'categoria'),
                'valores' => array_map(static function ($fila) {
                    return $fila['porcentaje'] ?? 0;
                }, $categorias),
            ],
            'series' => $series,
            'paises' => $this->paisesParaGrafico($paises),
            'sexo' => [
                'labels' => array_values(AdminModel::ETIQUETAS_SEXO),
                'valores' => array_values($sexo),
            ],
            'edad' => [
                'labels' => array_values(AdminModel::ETIQUETAS_EDAD),
                'valores' => array_values($edad),
            ],
        ];

        $this->render->renderiza('adminPanel', [
            'nombreAdmin' => $_SESSION['usuario'] ?? 'Administrador',
            'fechaGeneracion' => date('d/m/Y H:i'),
            'periodoEtiqueta' => AdminModel::PERIODOS[$periodo]['etiqueta'],
            'opcionesPeriodo' => $opcionesPeriodo,
            'm' => $m,
            'estadosPreguntas' => $estados,
            'minimoRespuestas' => AdminModel::MIN_RESPUESTAS_ACIERTOS,
            'minimoIntentos' => AdminModel::MIN_INTENTOS_PREGUNTA,
            'tablaAciertos' => $tablaAciertos,
            'hayAciertos' => !empty($tablaAciertos),
            'tablaCategorias' => $tablaCategorias,
            'hayRespuestas' => $metricas['respondidas'] > 0,
            'dificiles' => $this->tablaDificultad($dificultad['dificiles']),
            'faciles' => $this->tablaDificultad($dificultad['faciles']),
            'hayDificultad' => !empty($dificultad['dificiles']),
            'haySeries' => $metricas['partidasPeriodo'] > 0,
            'tablaSerieDia' => $this->tablaSerie($series['dia']),
            'tablaSerieSemana' => $this->tablaSerie($series['semana']),
            'tablaSerieMes' => $this->tablaSerie($series['mes']),
            'tablaPaises' => $tablaPaises,
            'hayPaises' => !empty($tablaPaises),
            'tablaSexo' => $tablaSexo,
            'tablaEdad' => $tablaEdad,
            'hayUsuarios' => $totalUsuarios > 0,
            // Se escapan <, >, & y comillas para que ningun texto cargado por usuarios pueda cerrar el <script>.
            'graficosJson' => json_encode(
                $graficos,
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ),
        ]);
    }

    private function tablaDificultad(array $preguntas): array
    {
        $filas = [];
        foreach ($preguntas as $i => $pregunta) {
            $filas[] = [
                'posicion' => $i + 1,
                'enunciado' => mb_strimwidth($pregunta['enunciado'], 0, self::LARGO_ENUNCIADO, '…', 'UTF-8'),
                'categoria' => $pregunta['categoria'],
                'intentos' => $pregunta['intentos'],
                'porcentaje' => $this->formatearPorcentaje($pregunta['porcentaje']),
            ];
        }
        return $filas;
    }

    /** Para la tabla solo se listan los periodos con partidas, del mas reciente al mas viejo. */
    private function tablaSerie(array $serie): array
    {
        $filas = [];
        foreach ($serie['labels'] as $i => $etiqueta) {
            if ($serie['valores'][$i] > 0) {
                $filas[] = ['etiqueta' => $etiqueta, 'total' => $serie['valores'][$i]];
            }
        }
        return array_reverse($filas);
    }

    /** Los primeros paises tal cual y el resto agrupado en "Otros". */
    private function paisesParaGrafico(array $paises): array
    {
        $labels = [];
        $valores = [];
        $otros = 0;
        foreach ($paises as $i => $fila) {
            if ($i < self::PAISES_TOP) {
                $labels[] = $fila['pais'];
                $valores[] = (int)$fila['total'];
            } else {
                $otros += (int)$fila['total'];
            }
        }
        if ($otros > 0) {
            $labels[] = 'Otros';
            $valores[] = $otros;
        }
        return ['labels' => $labels, 'valores' => $valores];
    }

    private function porcentajeDe(int $parte, int $total): string
    {
        return $total > 0 ? $this->formatearPorcentaje($parte * 100 / $total) : '0,0';
    }

    private function formatearPorcentaje($valor): string
    {
        return number_format((float)$valor, 1, ',', '');
    }

    private function formatearNumero($valor): string
    {
        return number_format((float)$valor, 1, ',', '');
    }

    private function exigirAdministrador(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }
        if ((int)($_SESSION['rol_id'] ?? 0) !== self::ROL_ADMINISTRADOR) {
            \Redirect::to('/usuario/lobby');
        }
    }
}
