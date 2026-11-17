<?php

namespace app\controller;

use app\model\PreguntaModel;
use Request;
use Redirect;

class PreguntaController
{
    public function __construct(private PreguntaModel $model, private $render) {}

    public function nueva()
    {
        $this->exigirLogin();
        $this->render->renderiza('preguntaNueva', ['categorias' => $this->model->categorias()]);
    }

    public function crear()
    {
        $usuarioId = $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Redirect::to('/pregunta/nueva');
        $enunciado = trim((string)Request::post('enunciado', ''));
        $categoria = (int)Request::post('categoria_id', 0);
        $correcta = (int)Request::post('correcta', 0);
        $listaAntigua = isset($_POST['opciones']);
        $opcionesTexto = $listaAntigua
            ? array_values(array_filter(array_map('trim', (array)$_POST['opciones']), fn($v) => $v !== ''))
            : array_values(array_filter(array_map('trim', [$_POST['opcion1'] ?? '', $_POST['opcion2'] ?? '', $_POST['opcion3'] ?? '', $_POST['opcion4'] ?? '']), fn($v) => $v !== ''));
        $indiceCorrecto = $listaAntigua ? $correcta : $correcta - 1;
        if ($enunciado === '' || mb_strlen($enunciado) > 500 || $categoria < 1 || count($opcionesTexto) < 2 || $indiceCorrecto < 0 || $indiceCorrecto >= count($opcionesTexto)) {
            $this->render->renderiza('preguntaNueva', ['categorias' => $this->model->categorias(), 'error' => 'Completá la pregunta, la categoría y al menos dos opciones.']);
            return;
        }
        $opciones = [];
        foreach ($opcionesTexto as $indice => $texto) {
            $opciones[] = ['texto' => $texto, 'es_correcta' => $indice === $indiceCorrecto ? 1 : 0];
        }
        $this->model->crearPropuesta($usuarioId, $categoria, $enunciado, $opciones);
        if (in_array((int)($_SESSION['rol_id'] ?? 0), [2, 3], true)) Redirect::to('/pregunta/inicio');
        $this->render->renderiza('preguntaNueva', ['categorias' => $this->model->categorias(), 'exito' => 'Tu pregunta quedó pendiente de revisión.']);
    }

    public function guardar() { $this->crear(); }

    public function reportar()
    {
        $usuarioId = $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') Redirect::to('/partida/jugar');
        $preguntaId = (int)Request::post('pregunta_id', 0);
        $motivo = trim((string)Request::post('motivo', ''));
        if ($preguntaId > 0 && $motivo !== '' && mb_strlen($motivo) <= 255
            && $this->model->guardarReporte($preguntaId, $usuarioId, $motivo) === 1) {
            $_SESSION['aviso_reporte'] = 'Gracias. El reporte quedó pendiente de revisión.';
        }
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

    public function pendientes()
    {
        $this->exigirModerador();
        Redirect::to('/pregunta/inicio');
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
            http_response_code(403);
            exit('No tenés permisos para moderar preguntas.');
        }
    }
}
