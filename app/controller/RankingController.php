<?php

namespace app\controller;

use app\model\RankingModel;

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

        $periodo = RankingModel::periodoValido(\Request::get('periodo'));
        $periodos = [];
        foreach (RankingModel::PERIODOS as $clave => $datos) {
            $periodos[] = ['clave' => $clave, 'etiqueta' => $datos['etiqueta'], 'activo' => $clave === $periodo];
        }

        $medallas = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
        $jugadores = [];

        foreach ($this->model->getRanking($periodo, 100) as $i => $fila) {
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
            'periodos' => $periodos,
            'periodoEtiqueta' => RankingModel::PERIODOS[$periodo]['etiqueta'],
            'esTotal' => $periodo === 'total',
        ]);
    }
}
