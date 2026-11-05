<?php

namespace app\model;

use Throwable;

class PartidaModel
{
    const AVISO_ABANDONO = 'Perdiste la partida: recargar la pregunta o salir sin responder termina el juego.';

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function crear($usuarioId): int
    {
        $this->db->execute("INSERT INTO partida (usuario_id) VALUES (?)", [$usuarioId]);
        return (int)$this->db->lastInsertId();
    }

    public function estaEnCurso($partidaId, $usuarioId): bool
    {
        $fila = $this->db->queryOne(
            "SELECT id FROM partida WHERE id = ? AND usuario_id = ? AND estado = 'en_curso'",
            [$partidaId, $usuarioId]
        );
        return $fila !== null;
    }

    public function cantidadRespuestas($partidaId): int
    {
        $fila = $this->db->queryOne(
            "SELECT COUNT(*) AS total FROM partida_pregunta WHERE partida_id = ?",
            [$partidaId]
        );
        return $fila ? (int)$fila['total'] : 0;
    }

    /**
     * Pregunta que ya se le mostro al jugador en esta partida y todavia no respondio
     * (devuelve null si no hay).
     */
    public function preguntaPendiente($partidaId)
    {
        return $this->db->queryOne(
            "SELECT id, pregunta_id FROM partida_pregunta
             WHERE partida_id = ? AND correcta IS NULL LIMIT 1",
            [$partidaId]
        );
    }

    /**
     * Marca el momento exacto en que una pregunta se le muestra al jugador usando la columna fecha.
     * Sirve para agregar un tiempo limite por pregunta mas adelante.
     */
    public function marcarMostrada($partidaId, $preguntaId): void
    {
        $this->db->execute(
            "INSERT INTO partida_pregunta (partida_id, pregunta_id) VALUES (?, ?)",
            [$partidaId, $preguntaId]
        );
    }

    /**
     * Cierra las partidas que el jugador dejo abiertas.
     *  - Una pregunta que se mostro y no se respondio cuenta como perdida (el jugador se fue o recargo).
     *  - Las partidas sin ninguna pregunta se borran; el resto se finaliza y conserva sus puntos.
     * @return bool true si se perdio una pregunta pendiente (el jugador abandono la partida)
     */
    public function cerrarEnCurso($usuarioId): bool
    {
        $this->db->beginTransaction();
        try {
            $perdidas = $this->db->execute(
                "UPDATE partida_pregunta pp
                 JOIN partida pa ON pa.id = pp.partida_id
                 SET pp.correcta = 0
                 WHERE pa.usuario_id = ? AND pa.estado = 'en_curso' AND pp.correcta IS NULL",
                [$usuarioId]
            );
            $this->db->execute(
                "DELETE FROM partida
                 WHERE usuario_id = ? AND estado = 'en_curso'
                   AND NOT EXISTS (SELECT 1 FROM partida_pregunta pp WHERE pp.partida_id = partida.id)",
                [$usuarioId]
            );
            $this->db->execute(
                "UPDATE partida SET estado = 'finalizada', fecha_fin = NOW()
                 WHERE usuario_id = ? AND estado = 'en_curso'",
                [$usuarioId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $perdidas > 0;
    }

    /**
     * Guarda la respuesta a la pregunta pendiente. Si es correcta suma un punto a la partida y
     * al total del jugador; si es incorrecta, la partida termina.
     * @return bool false si esa pregunta ya no estaba pendiente (respondida o abandonada)
     */
    public function registrarRespuesta($partidaId, $usuarioId, $preguntaId, $opcionId, $correcta): bool
    {
        $this->db->beginTransaction();
        try {
            // "AND correcta IS NULL" hace que una misma pregunta solo se pueda responder una vez,
            // aunque lleguen dos pedidos a la vez.
            $actualizadas = $this->db->execute(
                "UPDATE partida_pregunta SET opcion_elegida_id = ?, correcta = ?
                 WHERE partida_id = ? AND pregunta_id = ? AND correcta IS NULL",
                [$opcionId, $correcta ? 1 : 0, $partidaId, $preguntaId]
            );

            if ($actualizadas !== 1) {
                $this->db->rollBack();
                return false;
            }

            if ($correcta) {
                $this->db->execute(
                    "UPDATE partida SET puntaje = puntaje + 1 WHERE id = ? AND usuario_id = ?",
                    [$partidaId, $usuarioId]
                );
                $this->db->execute(
                    "UPDATE usuario SET puntaje_total = puntaje_total + 1 WHERE id = ?",
                    [$usuarioId]
                );
            } else {
                $this->db->execute(
                    "UPDATE partida SET estado = 'finalizada', fecha_fin = NOW()
                     WHERE id = ? AND usuario_id = ?",
                    [$partidaId, $usuarioId]
                );
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Ultimas partidas del jugador, de la mas reciente a la mas vieja. */
    public function ultimasPartidas($usuarioId, $limite = 10): array
    {
        $filas = $this->db->queryAll(
            "SELECT id, puntaje, fecha_inicio
             FROM partida
             WHERE usuario_id = ?
               AND (estado = 'finalizada'
                    OR EXISTS (SELECT 1 FROM partida_pregunta pp WHERE pp.partida_id = partida.id))
             ORDER BY fecha_inicio DESC, id DESC
             LIMIT " . (int)$limite,
            [$usuarioId]
        );

        return array_map(function ($fila) {
            $puntos = (int)$fila['puntaje'];
            return [
                'id' => (int)$fila['id'],
                'fecha' => date('d/m/Y H:i', strtotime($fila['fecha_inicio'])),
                'puntos' => $puntos,
                'etiquetaPuntos' => $puntos === 1 ? 'punto' : 'puntos',
            ];
        }, $filas);
    }
}
