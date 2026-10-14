<?php

namespace app;

use app\controller\GrupoController;
use app\model\GrupoModel;
use MustacheRender;
use Router;

// require_once("helper/Autoloader.php");
require_once __DIR__ . "/../helper/Autoloader.php";

class Configuration
{
    public function __construct()
    {
    }
    public function getGrupoController()
    {
        return new GrupoController(
            $this->getGrupoModel(),
            $this->getRender()
        );
    }

    // Los privados
    private function getGrupoModel()
    {
        return new GrupoModel();
    }

    private function getRender()
    {
        return new MustacheRender();
    }

    public function getRouter()
    {
        return new Router($this, "grupo", "show");
    }
}
