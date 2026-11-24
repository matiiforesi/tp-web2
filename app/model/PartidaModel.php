<?php

namespace app\model;

use Throwable;

class PartidaModel
{
    const VIDAS_MAXIMAS = 5;
    const MINUTOS_POR_VIDA = 30;
    const PREMIOS_RANKING_DIARIO = [1 => 100, 2 => 75, 3 => 50];
    const SEGUNDOS_POR_PREGUNTA = 15;
    const MARGEN_SEGUNDOS = 2;

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

    public function recursosUsuario($usuarioId): array
    {
        $fila = $this->db->queryOne(
            "SELECT vidas, dinero,
                    GREATEST(TIMESTAMPDIFF(SECOND, vidas_actualizadas_en, NOW()), 0) AS segundos_desde_actualizacion
             FROM usuario WHERE id = ?",
            [$usuarioId]
        );
        if (!$fila) return ['vidas' => 0, 'dinero' => 0];

        $vidas = min(self::VIDAS_MAXIMAS, (int)$fila['vidas']);
        $intervaloSegundos = self::MINUTOS_POR_VIDA * 60;
        $recuperadas = (int)floor((int)$fila['segundos_desde_actualizacion'] / $intervaloSegundos);
        if ($recuperadas > 0 && $vidas < self::VIDAS_MAXIMAS) {
            $vidas = min(self::VIDAS_MAXIMAS, $vidas + $recuperadas);
            if ($vidas >= self::VIDAS_MAXIMAS) {
                $this->db->execute("UPDATE usuario SET vidas = ?, vidas_actualizadas_en = NOW() WHERE id = ?", [$vidas, $usuarioId]);
            } else {
                $segundosConsumidos = $recuperadas * $intervaloSegundos;
                $this->db->execute(
                    "UPDATE usuario SET vidas = ?, vidas_actualizadas_en = DATE_ADD(vidas_actualizadas_en, INTERVAL ? SECOND) WHERE id = ?",
                    [$vidas, $segundosConsumidos, $usuarioId]
                );
            }
        }
        $actualizado = $this->db->queryOne("SELECT vidas, dinero FROM usuario WHERE id = ?", [$usuarioId]);
        $proximaVida = null;
        if ((int)$actualizado['vidas'] < self::VIDAS_MAXIMAS) {
            $reloj = $this->db->queryOne(
                "SELECT GREATEST(0, ? - GREATEST(TIMESTAMPDIFF(SECOND, vidas_actualizadas_en, NOW()), 0)) AS segundos_restantes
                 FROM usuario WHERE id = ?",
                [$intervaloSegundos, $usuarioId]
            );
            $proximaVida = (int)($reloj['segundos_restantes'] ?? $intervaloSegundos);
        }
        return [
            'vidas' => min(self::VIDAS_MAXIMAS, (int)$actualizado['vidas']),
            'dinero' => (int)$actualizado['dinero'],
            'proxima_vida_segundos' => $proximaVida,
        ];
    }

    public function gastarVida($usuarioId): int
    {
        $recursos = $this->recursosUsuario($usuarioId);
        $vidas = max(0, $recursos['vidas'] - 1);
        $this->db->execute("UPDATE usuario SET vidas = ?, vidas_actualizadas_en = NOW() WHERE id = ?", [$vidas, $usuarioId]);
        return $vidas;
    }

    public function acreditarDinero($usuarioId, $monto): void
    {
        $this->db->execute(
            "UPDATE usuario SET dinero = dinero + ? WHERE id = ?",
            [max(0, (int)$monto), $usuarioId]
        );
    }

    public function canjearEstrategia($usuarioId, int $costo): bool
    {
        return $this->db->execute(
                "UPDATE usuario SET dinero = dinero - ? WHERE id = ? AND dinero >= ?",
                [$costo, $usuarioId, $costo]
            ) === 1;
    }

    public function registrarSalto($partidaId, $preguntaId): bool
    {
        return $this->db->execute(
                "UPDATE partida_pregunta SET opcion_elegida_id = NULL, correcta = 0
             WHERE partida_id = ? AND pregunta_id = ? AND correcta IS NULL",
                [$partidaId, $preguntaId]
            ) === 1;
    }

    public function premiarRankingDiario($usuarioId): array
    {
        $recursos = $this->recursosUsuario($usuarioId);
        $fila = $this->db->queryOne("SELECT premio_ranking_fecha FROM usuario WHERE id = ?", [$usuarioId]);
        $hoy = date('Y-m-d');
        if (($fila['premio_ranking_fecha'] ?? null) === $hoy) return $recursos;
        $posicion = $this->db->queryOne(
            "SELECT 1 + COUNT(*) AS posicion FROM usuario u2 JOIN usuario u ON u.id = ?
             WHERE u2.rol_id = 1 AND u2.cuenta_validada = 1
             AND u2.puntaje_diario_fecha = CURDATE()
             AND (u2.puntaje_diario > CASE WHEN u.puntaje_diario_fecha = CURDATE() THEN u.puntaje_diario ELSE 0 END
                  OR (u2.puntaje_diario = CASE WHEN u.puntaje_diario_fecha = CURDATE() THEN u.puntaje_diario ELSE 0 END AND u2.id < u.id))",
            [$usuarioId]
        );
        $premio = self::PREMIOS_RANKING_DIARIO[(int)($posicion['posicion'] ?? 0)] ?? 10;
        $this->db->execute("UPDATE usuario SET dinero = dinero + ?, premio_ranking_fecha = ? WHERE id = ?", [$premio, $hoy, $usuarioId]);
        $recursos['dinero'] += $premio;
        return $recursos;
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

    /** Devuelve la pregunta sin responder de la partida, o null si no hay ninguna. */
    public function preguntaPendiente($partidaId)
    {
        return $this->db->queryOne(
            "SELECT id, pregunta_id, TIMESTAMPDIFF(SECOND, fecha, NOW()) AS segundos
             FROM partida_pregunta
             WHERE partida_id = ? AND correcta IS NULL LIMIT 1",
            [$partidaId]
        );
    }

    /** Registra cuándo se mostró la pregunta para controlar el tiempo y evitar repetirla. */
    public function marcarMostrada($partidaId, $preguntaId): void
    {
        $this->db->execute(
            "INSERT INTO partida_pregunta (partida_id, pregunta_id) VALUES (?, ?)",
            [$partidaId, $preguntaId]
        );
    }

    /** Cierra partidas abiertas y devuelve true si el jugador dejó una pregunta sin responder. */
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

    /** Guarda la respuesta y actualiza el puntaje o las vidas; devuelve false si ya se había respondido. */
    public function registrarRespuesta($partidaId, $usuarioId, $preguntaId, $opcionId, $correcta, $protegerVida = false): bool
    {
        $this->db->beginTransaction();
        try {
            // Esta condición evita registrar dos veces una respuesta si llegan pedidos simultáneos.
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
                    "UPDATE usuario
                     SET puntaje_total = puntaje_total + 1,
                         puntaje_diario = CASE WHEN puntaje_diario_fecha = CURDATE() THEN puntaje_diario + 1 ELSE 1 END,
                         puntaje_diario_fecha = CURDATE()
                     WHERE id = ?",
                    [$usuarioId]
                );
            } elseif (!$protegerVida) {
                $this->db->execute(
                    "UPDATE usuario
                     SET vidas = GREATEST(vidas - 1, 0), vidas_actualizadas_en = NOW()
                     WHERE id = ?",
                    [$usuarioId]
                );
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function finalizar($partidaId, $usuarioId): void
    {
        $this->db->execute(
            "UPDATE partida SET estado = 'finalizada', fecha_fin = NOW()
             WHERE id = ? AND usuario_id = ? AND estado = 'en_curso'",
            [$partidaId, $usuarioId]
        );
    }

    /** Devuelve las partidas recientes del jugador, desde la más nueva. */
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

    public function getPuntajePartida($partidaId)
    {
        $sql = "SELECT COUNT(*) as puntos FROM partida_pregunta WHERE partida_id = ? AND correcta = 1";
        $resultado = $this->db->queryOne($sql, [$partidaId]);
        return $resultado ? (int)$resultado['puntos'] : 0;
    }
}
