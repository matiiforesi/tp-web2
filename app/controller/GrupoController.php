<?php

namespace app\controller;

class GrupoController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    public function show()
    {
        $datos = $this->model->obtenerDatos();
        $datos['mensaje'] = 'Somos el Grupo 2 y estos son nuestros integrantes:';

        $this->render->renderiza('grupo', $datos);
    }
}
