<?php

namespace app;

use app\controller\GrupoController;
use app\controller\UsuarioController;
use app\model\GrupoModel;
use app\model\UsuarioModel;
use app\model\VerificacionCuentaModel;
use app\service\VerificacionCuentaService;
use Mailer;
use MustacheRender;
use MyDatabase;
use Router;

// require_once("helper/Autoloader.php");
require_once __DIR__ . "/../helper/Autoloader.php";

class Configuration
{
    private $database;
    private $config;

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
            $this->getRender(),
            $this->getVerificacionCuentaService()
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

    private function getVerificacionCuentaService()
    {
        $config = $this->getConfig();

        // La URL sale de config.ini, no del Host: si no, alguien podria falsificarlo
        // y hacer que el mail salga con un link a otro sitio.
        $appUrl = rtrim(trim($config["app_url"] ?? ""), "/");
        if (!preg_match('#^https?://#i', $appUrl)) {
            $appUrl = "";
        }

        return new VerificacionCuentaService(
            new VerificacionCuentaModel($this->getDatabase()),
            new Mailer($config),
            $appUrl
        );
    }

    private function getConfig()
    {
        if ($this->config === null) {
            $this->config = parse_ini_file(__DIR__ . "/../config/config.ini");
        }
        return $this->config;
    }

    // Una sola conexion por request, compartida por todos los modelos.
    private function getDatabase()
    {
        if ($this->database === null) {
            $config = $this->getConfig();
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
