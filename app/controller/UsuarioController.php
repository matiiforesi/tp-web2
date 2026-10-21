<?php

namespace app\controller;

use app\model\UsuarioModel;
use app\model\VerificacionCuentaModel;
use app\service\VerificacionCuentaService;
use ImageUpload;
use PDOException;
use Redirect;
use Request;
use Throwable;

class UsuarioController
{
    private $model;
    private $render;
    private $verificacion;

    public function __construct($model, $render, VerificacionCuentaService $verificacion)
    {
        $this->model = $model;
        $this->render = $render;
        $this->verificacion = $verificacion;
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

        // La contrasena es correcta, pero no se abre sesion hasta que valide la cuenta.
        if ((int)$usuarioValido['cuenta_validada'] !== 1) {
            $this->render->renderiza('login', [
                'error' => 'Tu cuenta todavía no está activada. Revisá tu mail (también la carpeta de spam) y entrá al link que te enviamos.',
                'usuario' => $usuario,
                'cuentaPendiente' => true,
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

        $errores = $this->model->validarRegistro($datos);
        // La foto llega como texto base64 (el hosting bloquea los envios multipart).
        [$erroresFoto, $bytesFoto] = ImageUpload::decodificarBase64(Request::post('foto_base64', ''));
        $errores = array_merge($errores, $erroresFoto);

        if (!empty($errores)) {
            $this->mostrarFormulario($datos, $errores);
            return;
        }

        $directorio = $this->directorioFotos();
        $nombreFoto = null;

        try {
            $nombreFoto = ImageUpload::guardarBytes($bytesFoto, $directorio);
            $usuarioId = $this->model->crear($datos, $nombreFoto);
        } catch (Throwable $e) {
            if ($nombreFoto !== null) {
                ImageUpload::eliminar($nombreFoto, $directorio);
            }
            \Log::error('Registro fallido: ' . $e->getMessage());

            // 1062 = clave duplicada
            $duplicado = $e instanceof PDOException && ($e->errorInfo[1] ?? null) === 1062;
            $mensaje = $duplicado
                ? 'El mail o el nombre de usuario ya están registrados.'
                : 'No pudimos crear tu cuenta. Intentá de nuevo en unos minutos.';
            $this->mostrarFormulario($datos, [$mensaje]);
            return;
        }

        // Si el mail falla la cuenta se crea igual, el servicio lo registra en el log
        // y se le indica al usuario que pida el reenvio del link.
        $mailEnviado = $this->verificacion->enviarMail($usuarioId, $datos['email'], $datos['nombre_completo']);

        $_SESSION['registro_exitoso'] = [
            'usuario' => $datos['usuario'],
            'email' => $datos['email'],
            'mailEnviado' => $mailEnviado,
        ];
        Redirect::to('/usuario/registroExitoso');
    }

    // GET /usuario/registroExitoso
    public function registroExitoso()
    {
        if (empty($_SESSION['registro_exitoso'])) {
            Redirect::to('/usuario/registro');
        }
        $datos = $_SESSION['registro_exitoso'];
        unset($_SESSION['registro_exitoso']);

        $this->render->renderiza('registroExitoso', $datos);
    }

    // GET /usuario/validar?token=...
    public function validar()
    {
        $token = Request::get('token', '');
        $resultado = is_string($token)
            ? $this->verificacion->validar($token)
            : VerificacionCuentaModel::INVALIDO;

        $this->render->renderiza('validar', [
            'exito' => $resultado === VerificacionCuentaModel::OK,
            'vencido' => $resultado === VerificacionCuentaModel::VENCIDO,
            'invalido' => $resultado === VerificacionCuentaModel::INVALIDO,
        ]);
    }

    // POST /usuario/reenviar
    public function reenviar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/usuario/login');
        }

        $usuario = trim((string)Request::post('usuario', ''));

        if ($usuario !== '') {
            try {
                $pendiente = $this->model->buscarPendientePorUsuario($usuario);
                if ($pendiente !== null) {
                    $this->verificacion->reenviar((int)$pendiente['id'], $pendiente['email'], $pendiente['nombre_completo']);
                }
            } catch (Throwable $e) {
                \Log::error('Reenvio de mail de activacion fallido: ' . $e->getMessage());
            }
        }

        $this->render->renderiza('login', [
            'info' => 'Te enviamos un mail nuevo para activar tu cuenta. '
                . 'Puede tardar unos minutos; revisá también la carpeta de spam. '
                . 'Si sigue fallando, esperá unos minutos antes de volver a intentar.',
            'usuario' => $usuario,
        ]);
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
