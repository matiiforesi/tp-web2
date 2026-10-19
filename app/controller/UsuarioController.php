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
    private $ubicacion;
    private $render;

    public function __construct($model, $ubicacion, $render)
    {
        $this->model = $model;
        $this->ubicacion = $ubicacion;
        $this->render = $render;
    }

    // GET /usuario/login
    public function login()
    {
        $this->render->renderiza('login', []);
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

        if (!$this->ubicacion->ciudadPerteneceAPais((int) $datos['ciudad_id'], (int) $datos['pais_id'])) {
            $errores[] = 'Elegí un país y una ciudad válidos.';
        }
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
            return trim((string) Request::post($clave, ''));
        };

        return [
            'nombre_completo'   => $texto('nombre_completo'),
            'anio_nacimiento'   => $texto('anio_nacimiento'),
            'sexo'              => $texto('sexo'),
            'pais_id'           => $texto('pais_id'),
            'ciudad_id'         => $texto('ciudad_id'),
            'email'             => $texto('email'),
            'usuario'           => $texto('usuario'),
            'password'          => $texto('password'),
            'password_repetida' => $texto('password_repetida'),
        ];
    }

    private function mostrarFormulario(array $datos, array $errores)
    {
        $paisId = (int) ($datos['pais_id'] ?? 0);
        $ciudadId = (int) ($datos['ciudad_id'] ?? 0);

        $paises = array_map(function ($p) use ($paisId) {
            $p['selected'] = ((int) $p['id'] === $paisId);
            return $p;
        }, $this->ubicacion->obtenerPaises());

        $todasLasCiudades = $this->ubicacion->obtenerCiudades();

        // Para volver a mostrar el formulario sin JS con la ciudad ya elegida.
        $ciudades = [];
        foreach ($todasLasCiudades as $c) {
            if ((int) $c['pais_id'] === $paisId) {
                $c['selected'] = ((int) $c['id'] === $ciudadId);
                $ciudades[] = $c;
            }
        }

        $sexos = [];
        foreach (UsuarioModel::SEXOS as $valor => $etiqueta) {
            $sexos[] = [
                'valor'    => $valor,
                'etiqueta' => $etiqueta,
                'selected' => (($datos['sexo'] ?? 'N') === $valor),
            ];
        }

        $this->render->renderiza('registro', [
            'errores'        => array_map(function ($m) { return ['mensaje' => $m]; }, $errores),
            'hayErrores'     => !empty($errores),
            'datos'          => $datos,
            'paises'         => $paises,
            'ciudades'       => $ciudades,
            'sexos'          => $sexos,
            'anioMaximo'     => (int) date('Y'),
            'anioMinimo'     => (int) date('Y') - 120,
            // JSON_HEX_* evita que un valor de la base pueda cerrar el <script>.
            'ciudadesJson'   => json_encode($todasLasCiudades, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]);
    }

    private function directorioFotos(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/perfiles';
    }
}
