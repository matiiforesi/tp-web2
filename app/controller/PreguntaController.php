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
        $opciones = array_values(array_filter(array_map('trim', (array)($_POST['opciones'] ?? [])), fn($v) => $v !== ''));
        $correcta = (int)Request::post('correcta', -1);
        if ($enunciado === '' || mb_strlen($enunciado) > 500 || $categoria < 1 || count($opciones) < 2 || $correcta < 0 || $correcta >= count($opciones)) {
            $this->render->renderiza('preguntaNueva', ['categorias' => $this->model->categorias(), 'error' => 'Completá la pregunta, la categoría y al menos dos opciones.']);
            return;
        }
        $this->model->crearPropuesta($usuarioId, $categoria, $enunciado, $opciones, $correcta);
        $this->render->renderiza('preguntaNueva', ['categorias' => $this->model->categorias(), 'exito' => 'Tu pregunta quedó pendiente de revisión.']);
    }

    public function pendientes()
    {
        $this->exigirModerador();
        $this->render->renderiza('preguntasPendientes', ['preguntas' => $this->model->pendientes(), 'hayPreguntas' => !empty($this->model->pendientes())]);
    }

    public function moderar()
    {
        $this->exigirModerador();
        $this->model->moderar((int)Request::post('id', 0), (string)Request::post('accion', ''), trim((string)Request::post('enunciado', '')));
        Redirect::to('/pregunta/pendientes');
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
