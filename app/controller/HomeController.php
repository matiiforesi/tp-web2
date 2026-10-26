<?php

namespace app\controller;

class HomeController
{
    private $render;

    public function __construct($render)
    {
        $this->render = $render;
    }

    public function show()
    {
        $data = [
            'titulo' => '¡Bienvenido al Juego de Preguntas!',
            'subtitulo' => 'Demostrá cuánto sabés, sumá puntos y competí por el primer puesto del ranking.',
            'estaLogueado' => isset($_SESSION['usuario_id']),
            'nombreUsuario' => $_SESSION['usuario'] ?? null,
        ];

        $this->render->renderiza('home', $data);
    }
}
