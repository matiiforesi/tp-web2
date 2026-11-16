<?php

namespace app\controller;

class PreguntaController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    // GET /pregunta/sugerir
    public function sugerir()
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }

        $categorias = $this->model->getCategorias();

        $this->render->renderiza('sugerirPregunta', [
            'categorias' => $categorias
        ]);
    }

    // POST /pregunta/guardar
    public function guardar()
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }

        $enunciado = \Request::post('enunciado');
        $categoriaId = filter_var(\Request::post('categoria_id'), FILTER_VALIDATE_INT);
        $opcion1 = \Request::post('opcion1');
        $opcion2 = \Request::post('opcion2');
        $opcion3 = \Request::post('opcion3');
        $opcion4 = \Request::post('opcion4');
        $correcta = \Request::post('correcta'); // Esto va a ser 1, 2, 3 o 4

        if (empty($enunciado) || !$categoriaId || empty($opcion1) || empty($opcion2) || empty($opcion3) || empty($opcion4) || !$correcta) {
            $_SESSION['aviso_sugerir'] = "Todos los campos son obligatorios.";
            \Redirect::to('/pregunta/sugerir');
        }

        $opciones = [
            ['texto' => $opcion1, 'es_correcta' => ($correcta == '1') ? 1 : 0],
            ['texto' => $opcion2, 'es_correcta' => ($correcta == '2') ? 1 : 0],
            ['texto' => $opcion3, 'es_correcta' => ($correcta == '3') ? 1 : 0],
            ['texto' => $opcion4, 'es_correcta' => ($correcta == '4') ? 1 : 0],
        ];

        $this->model->guardarSugerencia($enunciado, $categoriaId, $opciones);

        $_SESSION['aviso_lobby'] = "¡Gracias! Tu pregunta fue enviada para revisión.";
        \Redirect::to('/usuario/lobby');


    }
}