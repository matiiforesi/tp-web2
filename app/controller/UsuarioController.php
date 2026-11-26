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
    private $partidas;

    public function __construct($model, $render, VerificacionCuentaService $verificacion, $partidas)
    {
        $this->model = $model;
        $this->render = $render;
        $this->verificacion = $verificacion;
        $this->partidas = $partidas;
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

        if ((int)$usuarioValido['rol_id'] === 3) {
            Redirect::to('/admin/panel');
        }
        if ((int)$usuarioValido['rol_id'] === 2) {
            Redirect::to('/pregunta/inicio');
        }
        Redirect::to('/usuario/lobby');
    }

    // GET /usuario/lobby
    public function lobby()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/usuario/login');
        }

        $usuarioId = (int)$_SESSION['usuario_id'];
        $recursos = $this->partidas->premiarRankingDiario($usuarioId);

        // Solo las páginas de partida detectan abandonos; abrir el lobby no termina el juego.
        $aviso = $_SESSION['aviso_lobby'] ?? null;
        unset($_SESSION['aviso_lobby']);
        $exito = $_SESSION['exito_lobby'] ?? null;
        unset($_SESSION['exito_lobby']);

        $partidas = $this->partidas->ultimasPartidas($usuarioId, 10);

        $this->render->renderiza('lobby', [
            'aviso' => $aviso,
            'exito' => $exito,
            'usuario' => $_SESSION['usuario'],
            'puntajeTotal' => $this->model->getPuntajeTotal($usuarioId),
            'puntajeHoy' => $this->partidas->getPuntajeHoy($usuarioId),
            'vidas' => $recursos['vidas'],
            'dinero' => $recursos['dinero'],
            'proximaVidaSegundos' => $recursos['proxima_vida_segundos'],
            'partidas' => $partidas,
            'hayPartidas' => !empty($partidas),
        ]);
    }

    // GET /usuario/perfil
    public function perfil()
    {
        if (!isset($_SESSION['usuario_id'])) {
            Redirect::to('/usuario/login');
        }

        $perfil = $this->model->getPerfil((int)$_SESSION['usuario_id']);
        if ($perfil === null) {
            Redirect::to('/usuario/logout');
        }

        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', (string)$perfil['fecha_nacimiento']);
        $perfil['fecha_nacimiento'] = $fecha ? $fecha->format('d/m/Y') : $perfil['fecha_nacimiento'];
        $perfil['sexo'] = UsuarioModel::SEXOS[$perfil['sexo']] ?? $perfil['sexo'];
        $perfil['estadoCuenta'] = (int)$perfil['cuenta_validada'] === 1 ? 'Cuenta activada' : 'Pendiente de activación';
        $perfil['fecha_registro'] = date('d/m/Y', strtotime($perfil['fecha_registro']));
        $perfil['tieneFoto'] = !empty($perfil['foto_perfil']);
        $perfil['estadisticas'] = $this->model->getEstadisticasJuego((int)$_SESSION['usuario_id']);

        $this->render->renderiza('perfil', ['perfil' => $perfil]);
    }

    // GET /usuario/foto
    public function foto()
    {
        if (!isset($_SESSION['usuario_id'])) {
            http_response_code(404);
            return;
        }

        $nombre = $this->model->getFotoPerfil((int)$_SESSION['usuario_id']);
        if (!is_string($nombre) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/i', $nombre)) {
            http_response_code(404);
            return;
        }

        $ruta = rtrim($this->directorioFotos(), '/\\') . DIRECTORY_SEPARATOR . $nombre;
        if (!is_file($ruta)) {
            http_response_code(404);
            return;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=3600');
        readfile($ruta);
    }

    // GET /usuario/publico?usuario=...
    public function publico()
    {
        $usuario = trim((string)Request::get('usuario', ''));
        $perfil = $usuario === '' ? null : $this->model->getPerfilPublico($usuario);
        if ($perfil === null) {
            http_response_code(404);
            $this->render->renderiza('perfilPublico', ['encontrado' => false]);
            return;
        }

        $perfil['tieneFoto'] = !empty($perfil['foto_perfil']);
        $perfil['estadisticas'] = $this->model->getEstadisticasJuego((int)$perfil['id']);
        $this->render->renderiza('perfilPublico', ['encontrado' => true, 'perfil' => $perfil]);
    }

    // GET /usuario/fotoPublica?usuario=...
    public function fotoPublica()
    {
        $usuario = trim((string)Request::get('usuario', ''));
        $perfil = $usuario === '' ? null : $this->model->getPerfilPublico($usuario);
        $nombre = $perfil['foto_perfil'] ?? null;
        $this->servirFoto($nombre);
    }

    private function servirFoto($nombre): void
    {
        if (!is_string($nombre) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/i', $nombre)) {
            http_response_code(404);
            return;
        }

        $ruta = rtrim($this->directorioFotos(), '/\\') . DIRECTORY_SEPARATOR . $nombre;
        if (!is_file($ruta)) {
            http_response_code(404);
            return;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=3600');
        readfile($ruta);
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

        // Si falla el correo, la cuenta se conserva y el usuario puede pedir otro enlace.
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
            'fecha_nacimiento' => $texto('fecha_nacimiento'),
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
        if (!array_key_exists('fecha_nacimiento', $datos) || $datos['fecha_nacimiento'] === '') {
            $datos['fecha_nacimiento'] = '2000-01-01';
        }

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
            'fechaMaximaNacimiento' => (new \DateTimeImmutable('today'))->modify('-18 years')->format('Y-m-d'),
            'fechaMinimaNacimiento' => (new \DateTimeImmutable('today'))->modify('-120 years')->format('Y-m-d'),
        ]);
    }

    private function directorioFotos(): string
    {
        return getenv('TP_WEB2_UPLOAD_DIR') ?: (dirname(__DIR__, 2) . '/public/uploads/perfiles');
    }
}
