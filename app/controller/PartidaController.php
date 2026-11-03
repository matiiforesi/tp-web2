<?php

namespace app\controller;

class PartidaController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function jugar()
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }

        $pregunta = $this->model->getPreguntaAleatoria();

        if ($pregunta === null) {
            $this->render->renderiza('juegoVacio', []);
            return;
        }

        $puntajeTotal = $this->model->getPuntajeTotal($_SESSION['usuario_id']);

        $this->render->renderiza('partida', [
            'pregunta' => $pregunta,
            'puntajeTotal' => $puntajeTotal
        ]);
    }

    public function responder()
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }

        $opcionId = $_POST['opcion_id'] ?? null;

        if ($opcionId === null) {
            \Redirect::to('/partida/jugar');
        }

        $esCorrecta = $this->model->esRespuestaCorrecta($opcionId);

        if ($esCorrecta) {
            $this->model->sumarPunto($_SESSION['usuario_id']);
            \Redirect::to('/partida/jugar');
        } else {
            \Redirect::to('/usuario/lobby');
        }
    }
}