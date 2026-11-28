<?php

namespace app\controller;

use app\model\PreguntaModel;
use Request;
use Redirect;

class PreguntaController
{
    public function __construct(private PreguntaModel $model, private $render) {}

    // POST /pregunta/guardar: sugerencia de pregunta desde el lobby (queda pendiente de revision).
    public function guardar()
    {
        $usuarioId = $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Redirect::to('/usuario/lobby');
        $enunciado = trim((string)Request::post('enunciado', ''));
        $categoria = (int)Request::post('categoria_id', 0);
        $indiceCorrecto = (int)Request::post('correcta', 0) - 1;
        $opcionesTexto = array_values(array_filter(
            array_map('trim', [$_POST['opcion1'] ?? '', $_POST['opcion2'] ?? '', $_POST['opcion3'] ?? '', $_POST['opcion4'] ?? '']),
            fn($v) => $v !== ''
        ));
        if ($enunciado === '' || mb_strlen($enunciado) > 500 || $categoria < 1 || count($opcionesTexto) < 2 || $indiceCorrecto < 0 || $indiceCorrecto >= count($opcionesTexto)) {
            $_SESSION['aviso_lobby'] = 'Completá la pregunta, la categoría y al menos dos opciones.';
            Redirect::to('/usuario/lobby');
        }
        $opciones = [];
        foreach ($opcionesTexto as $indice => $texto) {
            $opciones[] = ['texto' => $texto, 'es_correcta' => $indice === $indiceCorrecto ? 1 : 0];
        }
        $this->model->crearPropuesta($usuarioId, $categoria, $enunciado, $opciones);
        if (in_array((int)($_SESSION['rol_id'] ?? 0), [2, 3], true)) Redirect::to('/pregunta/inicio');
        $_SESSION['exito_lobby'] = 'Tu pregunta quedó pendiente de revisión.';
        Redirect::to('/usuario/lobby');
    }

    public function reportar()
    {
        $usuarioId = $this->exigirLogin();
        $esAjax = Request::post('ajax', '') === '1';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Redirect::to('/partida/jugar');
        $preguntaId = (int)Request::post('pregunta_id', 0);
        $motivo = trim((string)Request::post('motivo', ''));
        $detalle = trim((string)Request::post('detalle', ''));
        if ($detalle !== '') {
            $motivo = $motivo === '' ? $detalle : $motivo . ': ' . $detalle;
        }
        $guardado = $preguntaId > 0 && $motivo !== '' && mb_strlen($motivo) <= 255
            && $this->model->guardarReporte($preguntaId, $usuarioId, $motivo) === 1;
        $mensaje = $guardado
            ? 'Gracias. El reporte quedó pendiente de revisión.'
            : 'No se pudo enviar el reporte: indicá el motivo (hasta 255 caracteres) y recordá que no podés reportar dos veces la misma pregunta.';

        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (!$guardado) http_response_code(422);
            echo json_encode(['ok' => $guardado, 'mensaje' => $mensaje]);
            return;
        }
        $_SESSION['aviso_reporte'] = $mensaje;
        Redirect::to('/partida/resultado');
    }

    public function inicio()
    {
        $this->exigirModerador();
        $resumen = $this->model->resumenModeracion();
        $this->render->renderiza('editorInicio', array_merge($resumen, [
            'preguntas' => $this->model->pendientes(),
            'hayPreguntas' => $resumen['cantidadPendientes'] > 0,
            'reportes' => $this->model->reportesPendientes(),
            'hayReportes' => $resumen['cantidadReportes'] > 0,
            'nombreEditor' => $_SESSION['usuario'] ?? 'Editor',
        ]));
    }

    public function moderar()
    {
        $this->exigirModerador();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->moderar((int)Request::post('id', 0), (string)Request::post('accion', ''), trim((string)Request::post('enunciado', '')));
        }
        Redirect::to('/pregunta/inicio');
    }

    public function moderarReporte()
    {
        $this->exigirModerador();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->moderarReporte((int)Request::post('id', 0), (string)Request::post('accion', ''));
        }
        Redirect::to('/pregunta/inicio');
    }

    const POR_PAGINA = 20;
    const ETIQUETAS_ESTADO = ['activa' => 'Activa', 'pendiente' => 'Pendiente', 'reportada' => 'Reportada', 'baja' => 'De baja'];

    public function listado()
    {
        $this->exigirModerador();
        $texto = trim((string)Request::get('q', ''));
        $categoriaId = (int)Request::get('categoria', 0);
        $estado = (string)Request::get('estado', '');
        if (!isset(self::ETIQUETAS_ESTADO[$estado])) $estado = '';
        $pagina = max(1, (int)Request::get('pagina', 1));

        $resultado = $this->model->listarPreguntas($texto, $categoriaId, $estado, $pagina, self::POR_PAGINA);
        $paginas = max(1, (int)ceil($resultado['total'] / self::POR_PAGINA));

        $preguntas = array_map(function ($p) {
            $respondidas = (int)$p['respondidas'];
            return [
                'id' => (int)$p['id'],
                'enunciado' => $p['enunciado'],
                'categoria' => $p['categoria'],
                'categoriaColor' => $p['categoria_color'],
                'estado' => $p['estado'],
                'estadoEtiqueta' => self::ETIQUETAS_ESTADO[$p['estado']] ?? $p['estado'],
                'estaDeBaja' => $p['estado'] === 'baja',
                'autor' => $p['autor'],
                'correcta' => $p['correcta'] ?? '—',
                'respondidas' => $respondidas,
                'acierto' => $respondidas > 0 ? round((int)$p['acertadas'] * 100 / $respondidas) . ' %' : '—',
                'reportes' => (int)$p['reportes'],
            ];
        }, $resultado['preguntas']);

        $filtros = ['q' => $texto, 'categoria' => $categoriaId ?: '', 'estado' => $estado];
        $url = fn($n) => '/pregunta/listado?' . http_build_query(array_filter($filtros + ['pagina' => $n]));

        $this->render->renderiza('editorPreguntas', [
            'preguntas' => $preguntas,
            'hayPreguntas' => count($preguntas) > 0,
            'total' => $resultado['total'],
            'texto' => $texto,
            'categorias' => $this->opcionesCategoria($categoriaId),
            'estados' => array_map(
                fn($clave) => ['valor' => $clave, 'etiqueta' => self::ETIQUETAS_ESTADO[$clave], 'seleccionado' => $clave === $estado],
                array_keys(self::ETIQUETAS_ESTADO)
            ),
            'hayFiltros' => $texto !== '' || $categoriaId > 0 || $estado !== '',
            'pagina' => $pagina,
            'paginas' => $paginas,
            'hayPaginacion' => $paginas > 1,
            'urlAnterior' => $pagina > 1 ? $url($pagina - 1) : null,
            'urlSiguiente' => $pagina < $paginas ? $url($pagina + 1) : null,
            'volver' => $volver = $this->volverSeguro($_SERVER['REQUEST_URI'] ?? ''),
            'volverCodificado' => rawurlencode($volver),
            'exito' => $this->tomarFlash('exito_editor'),
            'aviso' => $this->tomarFlash('aviso_editor'),
        ]);
    }

    public function nueva()
    {
        $this->exigirModerador();
        $this->mostrarFormulario(['id' => 0, 'categoria_id' => 0, 'enunciado' => '', 'estado' => 'activa', 'opciones' => []], null, '/pregunta/listado');
    }

    public function editar()
    {
        $this->exigirModerador();
        $pregunta = $this->model->preguntaParaEditar((int)Request::get('id', 0));
        if ($pregunta === null) {
            $_SESSION['aviso_editor'] = 'La pregunta no existe.';
            Redirect::to('/pregunta/listado');
        }
        $this->mostrarFormulario($pregunta, null, $this->volverSeguro((string)Request::get('volver', '')));
    }

    public function guardarEditor()
    {
        $editorId = $this->exigirModerador();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Redirect::to('/pregunta/listado');

        $id = (int)Request::post('id', 0);
        $volver = $this->volverSeguro((string)Request::post('volver', ''));
        $categoriaId = (int)Request::post('categoria_id', 0);
        $enunciado = trim((string)Request::post('enunciado', ''));
        $estado = (string)Request::post('estado', 'activa');
        $correcta = (int)Request::post('correcta', 0);

        $existente = null;
        if ($id > 0) {
            $existente = $this->model->preguntaParaEditar($id);
            if ($existente === null) {
                $_SESSION['aviso_editor'] = 'La pregunta no existe.';
                Redirect::to('/pregunta/listado');
            }
        }
        $opcionesExistentes = [];
        foreach ($existente['opciones'] ?? [] as $opcion) {
            $opcionesExistentes[(int)$opcion['id']] = $opcion;
        }

        $opciones = [];
        $aQuitar = [];
        $errores = [];
        for ($i = 1; $i <= 4; $i++) {
            $texto = trim((string)Request::post('opcion' . $i, ''));
            $opcionId = (int)Request::post('opcion_id' . $i, 0);
            if (!isset($opcionesExistentes[$opcionId])) $opcionId = 0;
            if ($texto === '') {
                if ($opcionId > 0) {
                    if ($opcionesExistentes[$opcionId]['usada']) {
                        $errores[] = 'La opción ' . chr(64 + $i) . ' ya la eligieron jugadores: no se puede borrar, solo cambiarle el texto.';
                    } else {
                        $aQuitar[] = $opcionId;
                    }
                }
                if ($correcta === $i) $errores[] = 'La opción marcada como correcta no puede estar vacía.';
                continue;
            }
            if (mb_strlen($texto) > 255) $errores[] = 'La opción ' . chr(64 + $i) . ' supera los 255 caracteres.';
            $opciones[] = ['id' => $opcionId, 'texto' => $texto, 'es_correcta' => $correcta === $i ? 1 : 0];
        }

        if ($enunciado === '' || mb_strlen($enunciado) > 500) $errores[] = 'El enunciado es obligatorio y puede tener hasta 500 caracteres.';
        if (!$this->model->categoriaExiste($categoriaId)) $errores[] = 'Elegí una categoría.';
        if (!in_array($estado, PreguntaModel::ESTADOS_EDITABLES, true)) $errores[] = 'El estado no es válido.';
        if (count($opciones) < 2) $errores[] = 'La pregunta necesita al menos dos opciones.';
        if ($correcta < 1 || $correcta > 4) $errores[] = 'Marcá cuál es la opción correcta.';
        $textos = array_map(fn($o) => mb_strtolower($o['texto']), $opciones);
        if (count($textos) !== count(array_unique($textos))) $errores[] = 'Hay opciones repetidas.';

        if ($errores) {
            $enviada = ['id' => $id, 'categoria_id' => $categoriaId, 'enunciado' => $enunciado, 'estado' => $estado, 'opciones' => []];
            for ($i = 1; $i <= 4; $i++) {
                $opcionId = (int)Request::post('opcion_id' . $i, 0);
                $enviada['opciones'][] = [
                    'id' => isset($opcionesExistentes[$opcionId]) ? $opcionId : 0,
                    'texto' => trim((string)Request::post('opcion' . $i, '')),
                    'es_correcta' => $correcta === $i ? 1 : 0,
                    'usada' => $opcionesExistentes[$opcionId]['usada'] ?? 0,
                ];
            }
            $this->mostrarFormulario($enviada, $errores, $volver);
            return;
        }

        if ($id > 0) {
            $this->model->actualizarPregunta($id, $categoriaId, $enunciado, $estado, $opciones, $aQuitar);
            $_SESSION['exito_editor'] = 'Pregunta #' . $id . ' actualizada.';
        } else {
            $nuevoId = $this->model->crearPregunta($editorId, $categoriaId, $enunciado, $estado, $opciones);
            $_SESSION['exito_editor'] = 'Pregunta #' . $nuevoId . ' creada.';
        }
        Redirect::to($volver);
    }

    public function cambiarEstado()
    {
        $this->exigirModerador();
        $volver = $this->volverSeguro((string)Request::post('volver', ''));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)Request::post('id', 0);
            $estado = (string)Request::post('estado', '');
            if ($this->model->cambiarEstado($id, $estado)) {
                $_SESSION['exito_editor'] = $estado === 'baja' ? 'Pregunta #' . $id . ' dada de baja.' : 'Pregunta #' . $id . ' reactivada.';
            } else {
                $_SESSION['aviso_editor'] = 'No se pudo cambiar el estado de la pregunta.';
            }
        }
        Redirect::to($volver);
    }

    private function mostrarFormulario(array $pregunta, ?array $errores, string $volver): void
    {
        $opciones = array_values($pregunta['opciones']);
        $filas = [];
        for ($i = 0; $i < 4; $i++) {
            $opcion = $opciones[$i] ?? ['id' => 0, 'texto' => '', 'es_correcta' => 0, 'usada' => 0];
            $filas[] = [
                'numero' => $i + 1,
                'letra' => chr(65 + $i),
                'id' => (int)$opcion['id'],
                'texto' => $opcion['texto'],
                'esCorrecta' => (int)$opcion['es_correcta'] === 1,
                'usada' => (bool)$opcion['usada'],
                'obligatoria' => $i < 2,
            ];
        }
        $esNueva = (int)$pregunta['id'] === 0;
        $estados = PreguntaModel::ESTADOS_EDITABLES;
        if (!in_array($pregunta['estado'], $estados, true)) $estados[] = $pregunta['estado'];

        $this->render->renderiza('editorFormulario', [
            'id' => (int)$pregunta['id'],
            'esNueva' => $esNueva,
            'titulo' => $esNueva ? 'Nueva pregunta' : 'Editar pregunta #' . (int)$pregunta['id'],
            'enunciado' => $pregunta['enunciado'],
            'categorias' => $this->opcionesCategoria((int)$pregunta['categoria_id']),
            'estados' => array_map(
                fn($clave) => ['valor' => $clave, 'etiqueta' => self::ETIQUETAS_ESTADO[$clave] ?? $clave, 'seleccionado' => $clave === $pregunta['estado']],
                $estados
            ),
            'opciones' => $filas,
            'errores' => $errores ? array_map(fn($e) => ['mensaje' => $e], array_values(array_unique($errores))) : [],
            'hayErrores' => !empty($errores),
            'volver' => $volver,
        ]);
    }

    private function opcionesCategoria(int $seleccionada): array
    {
        return array_map(
            fn($c) => ['id' => (int)$c['id'], 'nombre' => $c['nombre'], 'seleccionada' => (int)$c['id'] === $seleccionada],
            $this->model->todasLasCategorias()
        );
    }

    private function volverSeguro(string $url): string
    {
        return preg_match('#^/pregunta/(listado|inicio)(\?[^\s]*)?$#', $url) ? $url : '/pregunta/listado';
    }

    private function tomarFlash(string $clave): ?string
    {
        $valor = $_SESSION[$clave] ?? null;
        unset($_SESSION[$clave]);
        return $valor;
    }

    private function exigirLogin(): int
    {
        if (!isset($_SESSION['usuario_id'])) Redirect::to('/usuario/login');
        return (int)$_SESSION['usuario_id'];
    }

    private function exigirModerador(): int
    {
        $usuarioId = $this->exigirLogin();
        if (!in_array((int)($_SESSION['rol_id'] ?? 0), [2, 3], true)) {
            $_SESSION['aviso_lobby'] = 'No tenés permisos para moderar preguntas.';
            Redirect::to('/usuario/lobby');
        }
        return $usuarioId;
    }
}
