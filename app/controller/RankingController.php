<?php

namespace app\controller;

class RankingController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    // GET /ranking/ver
    public function ver()
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }

        $medallas = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
        $jugadores = [];

        foreach ($this->model->getRanking(100) as $i => $fila) {
            $posicion = $i + 1;
            $jugadores[] = [
                'id' => (int)$fila['id'],
                'posicion' => $posicion,
                'medalla' => $medallas[$posicion] ?? '',
                'usuario' => $fila['usuario'],
                'pais' => $fila['pais'],
                'puntaje' => (int)$fila['puntaje'],
                'esUsuarioActual' => (int)$fila['id'] === (int)$_SESSION['usuario_id'],
            ];
        }

        $this->render->renderiza('ranking', [
            'jugadores' => $jugadores,
            'hayJugadores' => !empty($jugadores),
        ]);
    }
}
