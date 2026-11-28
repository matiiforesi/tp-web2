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

    private function exigirLogin(): int
    {
        if (!isset($_SESSION['usuario_id'])) Redirect::to('/usuario/login');
        return (int)$_SESSION['usuario_id'];
    }

    private function exigirModerador(): void
    {
        $this->exigirLogin();
        if (!in_array((int)($_SESSION['rol_id'] ?? 0), [2, 3], true)) {
            $_SESSION['aviso_lobby'] = 'No tenés permisos para moderar preguntas.';
            Redirect::to('/usuario/lobby');
        }
    }
}
