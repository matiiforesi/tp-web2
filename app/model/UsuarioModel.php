<?php

namespace app\model;

class UsuarioModel
{
    const SEXOS = [
        'M' => 'Masculino',
        'F' => 'Femenino',
        'N' => 'Prefiero no cargarlo',
    ];
    const ROL_JUGADOR = 1;

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function validarRegistro(array $d): array
    {
        $errores = [];

        $largoNombre = mb_strlen($d['nombre_completo']);
        if ($largoNombre < 3 || $largoNombre > 100) {
            $errores[] = 'El nombre completo debe tener entre 3 y 100 caracteres.';
        }

        $anio = filter_var($d['anio_nacimiento'], FILTER_VALIDATE_INT);
        $anioActual = (int)date('Y');
        if ($anio === false || $anio < $anioActual - 120 || $anio > $anioActual) {
            $errores[] = 'El año de nacimiento no es válido.';
        }

        if (!array_key_exists($d['sexo'], self::SEXOS)) {
            $errores[] = 'Elegí una opción válida de sexo.';
        }

        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 100) {
            $errores[] = 'El mail ingresado no tiene un formato válido.';
        } elseif ($this->emailExiste($d['email'])) {
            $errores[] = 'Ya existe una cuenta registrada con ese mail.';
        }

        if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $d['usuario'])) {
            $errores[] = 'El nombre de usuario debe tener de 3 a 20 caracteres. Puede incluir letras, números o guión bajo.';
        } elseif ($this->usuarioExiste($d['usuario'])) {
            $errores[] = 'Ese nombre de usuario ya está en uso.';
        }

        if (strlen($d['password']) < 8 || strlen($d['password']) > 20) {
            $errores[] = 'La contraseña debe tener entre 8 y 20 caracteres.';
        } elseif ($d['password'] !== $d['password_repetida']) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        return $errores;
    }

    public function emailExiste($email): bool
    {
        return $this->db->queryOne("SELECT id FROM usuario WHERE email = ?", [$email]) !== null;
    }

    public function usuarioExiste($usuario): bool
    {
        return $this->db->queryOne("SELECT id FROM usuario WHERE usuario = ?", [$usuario]) !== null;
    }

    public function buscarPorUsuario(string $usuario)
    {
        return $this->db->queryOne(
            "SELECT id, usuario, password_hash, rol_id, cuenta_validada
             FROM usuario
             WHERE usuario = ?",
            [$usuario]
        );
    }

    public function autenticar(string $usuario, string $password)
    {
        $u = $this->buscarPorUsuario($usuario);
        if ($u === null) {
            return false;
        }

        if (!password_verify($password, $u['password_hash'])) {
            return false;
        }

        return $u;
    }

    /** Inserta el usuario SIEMPRE como jugador y devuelve su id. Lanza PDOException si falla. */
    public function crear(array $d, $fotoPerfil): int
    {
        $this->db->execute(
            "INSERT INTO usuario
                (rol_id, nombre_completo, anio_nacimiento, sexo, pais_id, ciudad_id,
                 email, usuario, password_hash, foto_perfil)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                self::ROL_JUGADOR,
                $d['nombre_completo'],
                (int)$d['anio_nacimiento'],
                $d['sexo'],
                (int)$d['pais_id'],
                (int)$d['ciudad_id'],
                $d['email'],
                $d['usuario'],
                password_hash($d['password'], PASSWORD_DEFAULT),
                $fotoPerfil,
            ]
        );
        return (int)$this->db->lastInsertId();
    }
}
