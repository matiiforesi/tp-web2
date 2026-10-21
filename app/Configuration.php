<?php

namespace app;

use app\controller\GrupoController;
use app\controller\UsuarioController;
use app\model\GrupoModel;
use app\model\UsuarioModel;
use MustacheRender;
use MyDatabase;
use Router;

// require_once("helper/Autoloader.php");
require_once __DIR__ . "/../helper/Autoloader.php";

class Configuration
{
    private $database;

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

    public function getUsuarioController()
    {
        return new UsuarioController(
            $this->getUsuarioModel(),
            $this->getRender()
        );
    }

    // Los privados
    private function getGrupoModel()
    {
        return new GrupoModel();
    }

    private function getUsuarioModel()
    {
        return new UsuarioModel($this->getDatabase());
    }

    // Una sola conexion por request, compartida por todos los modelos.
    private function getDatabase()
    {
        if ($this->database === null) {
            $config = parse_ini_file(__DIR__ . "/../config/config.ini");
            $this->database = new MyDatabase(
                $config["db_host"],
                $config["db_user"],
                $config["db_pass"],
                $config["db_name"]
            );
        }
        return $this->database;
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
