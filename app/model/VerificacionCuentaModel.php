<?php

namespace app\model;

class VerificacionCuentaModel
{
    const OK = 'ok';
    const INVALIDO = 'invalido';
    const VENCIDO = 'vencido';

    const HORAS_VIGENCIA = 24;
    const SEGUNDOS_ESPERA_REENVIO = 300;

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /** Genera y devuelve un token nuevo para el usuario, invalida los anteriores. */
    public function crearToken(int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32));

        $this->eliminarPorUsuario($usuarioId);
        $this->db->execute(
            "INSERT INTO verificacion_cuenta (usuario_id, token_hash, expira)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " . (int)self::HORAS_VIGENCIA . " HOUR))",
            [$usuarioId, hash('sha256', $token)]
        );

        return $token;
    }

    public function eliminarPorUsuario(int $usuarioId): void
    {
        $this->db->execute("DELETE FROM verificacion_cuenta WHERE usuario_id = ?", [$usuarioId]);
    }

    /** Devuelve OK, VENCIDO o INVALIDO. */
    public function validar(string $token): string
    {
        // Descarta cualquier token con formato invalido.
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return self::INVALIDO;
        }

        $fila = $this->db->queryOne(
            "SELECT usuario_id, (expira < NOW()) AS vencido
             FROM verificacion_cuenta
             WHERE token_hash = ?",
            [hash('sha256', $token)]
        );

        if ($fila === null) {
            return self::INVALIDO;
        }
        if ((int)$fila['vencido'] === 1) {
            return self::VENCIDO;
        }

        $usuarioId = (int)$fila['usuario_id'];
        $this->db->execute("UPDATE usuario SET cuenta_validada = 1 WHERE id = ?", [$usuarioId]);
        $this->eliminarPorUsuario($usuarioId);

        return self::OK;
    }

    /** false si ya se mando un mail hace menos de SEGUNDOS_ESPERA_REENVIO segundos. */
    public function puedeReenviar(int $usuarioId): bool
    {
        $fila = $this->db->queryOne(
            "SELECT TIMESTAMPDIFF(SECOND, enviado_en, NOW()) AS segundos
             FROM verificacion_cuenta
             WHERE usuario_id = ?
             ORDER BY enviado_en DESC
             LIMIT 1",
            [$usuarioId]
        );

        return $fila === null || (int)$fila['segundos'] >= self::SEGUNDOS_ESPERA_REENVIO;
    }
}
