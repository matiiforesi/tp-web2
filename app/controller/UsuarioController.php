<?php

namespace app\controller;

use app\model\UsuarioModel;
use ImageUpload;
use PDOException;
use Redirect;
use Request;
use Throwable;

class UsuarioController
{
    private $model;
    private $render;

    public function __construct($model, $render)
    {
        $this->model = $model;
        $this->render = $render;
    }

    // GET /usuario/login
    public function login()
    {
        if (isset($_SESSION['usuario_id'])) {
            Redirect::to('/usuario/lobby');
        }

        $this->render->renderiza('login', []);
    }

    // POST /usuario/autenticar
    public function autenticar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/usuario/login');
        }

        $usuario = trim(Request::post('usuario', ''));
        $password = Request::post('password', '');

        if ($usuario === '' || $password === '') {
            $this->render->renderiza('login', [
                'error' => 'Por favor, completá todos los campos.',
                'usuario' => $usuario,
            ]);
            return;
        }

        $usuarioValido = $this->model->autenticar($usuario, $password);

        if (!$usuarioValido) {
            $this->render->renderiza('login', [
                'error' => 'Usuario o contraseña incorrectos.',
                'usuario' => $usuario,
            ]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int)$usuarioValido['id'];
        $_SESSION['usuario'] = $usuarioValido['usuario'];
        $_SESSION['rol_id'] = (int)$usuarioValido['rol_id'];

        Redirect::to('/usuario/lobby');
    }

    // GET /usuario/lobby
    public function lobby()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/usuario/login');
        }

        $this->render->renderiza('lobby', [
            'usuario' => $_SESSION['usuario'],
        ]);
    }

    // GET /usuario/logout
    public function logout()
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        Redirect::to('/usuario/login');
    }

    // GET /usuario/registro
    public function registro()
    {
        $this->mostrarFormulario([], []);
    }

    // POST /usuario/registrar
    public function registrar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/usuario/registro');
        }

        $datos = $this->leerDatosDelFormulario();
        $foto = $_FILES['foto_perfil'] ?? null;

        $errores = $this->model->validarRegistro($datos);
        $errores = array_merge($errores, ImageUpload::validar($foto));

        if (!empty($errores)) {
            $this->mostrarFormulario($datos, $errores);
            return;
        }

        $directorio = $this->directorioFotos();
        $nombreFoto = null;

        try {
            $nombreFoto = ImageUpload::guardar($foto, $directorio);
            $this->model->crear($datos, $nombreFoto);
        } catch (Throwable $e) {
            if ($nombreFoto !== null) {
                ImageUpload::eliminar($nombreFoto, $directorio);
            }
            \Log::error('Registro fallido: ' . $e->getMessage());

            // 1062 = clave duplicada: otro registro se nos adelanto entre la validacion y el INSERT.
            $duplicado = $e instanceof PDOException && ($e->errorInfo[1] ?? null) === 1062;
            $mensaje = $duplicado
                ? 'El mail o el nombre de usuario ya están registrados.'
                : 'No pudimos crear tu cuenta. Intentá de nuevo en unos minutos.';
            $this->mostrarFormulario($datos, [$mensaje]);
            return;
        }

        $_SESSION['registro_exitoso'] = $datos['usuario'];
        Redirect::to('/usuario/registroExitoso');
    }

    // GET /usuario/registroExitoso
    public function registroExitoso()
    {
        if (empty($_SESSION['registro_exitoso'])) {
            Redirect::to('/usuario/registro');
        }
        $usuario = $_SESSION['registro_exitoso'];
        unset($_SESSION['registro_exitoso']);

        $this->render->renderiza('registroExitoso', ['usuario' => $usuario]);
    }

    private function leerDatosDelFormulario(): array
    {
        $texto = function ($clave) {
            return trim((string)Request::post($clave, ''));
        };

        return [
            'nombre_completo' => $texto('nombre_completo'),
            'anio_nacimiento' => $texto('anio_nacimiento'),
            'sexo' => $texto('sexo'),
            'pais_codigo' => strtoupper($texto('pais_codigo')),
            'pais' => $texto('pais'),
            'ciudad' => $texto('ciudad'),
            'latitud' => $texto('latitud'),
            'longitud' => $texto('longitud'),
            'email' => $texto('email'),
            'usuario' => $texto('usuario'),
            'password' => $texto('password'),
            'password_repetida' => $texto('password_repetida'),
        ];
    }

    private function mostrarFormulario(array $datos, array $errores)
    {
        $sexos = [];
        foreach (UsuarioModel::SEXOS as $valor => $etiqueta) {
            $sexos[] = [
                'valor' => $valor,
                'etiqueta' => $etiqueta,
                'selected' => (($datos['sexo'] ?? 'N') === $valor),
            ];
        }

        $this->render->renderiza('registro', [
            'errores' => array_map(function ($m) {
                return ['mensaje' => $m];
            }, $errores),
            'hayErrores' => !empty($errores),
            'datos' => $datos,
            'sexos' => $sexos,
            'anioMaximo' => (int)date('Y'),
            'anioMinimo' => (int)date('Y') - 120,
        ]);
    }

    private function directorioFotos(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/perfiles';
    }
}
