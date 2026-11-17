<?php

namespace app\model;

class PreguntaModel
{
    private $db;

    public function categorias(): array
    {
        $categorias = $this->db->queryAll(
            "SELECT c.id, c.nombre, c.color FROM categoria c
             WHERE EXISTS (SELECT 1 FROM pregunta p WHERE p.categoria_id = c.id AND p.estado = 'activa')
             ORDER BY c.nombre"
        );
        foreach ($categorias as &$categoria) {
            $categoria['esGeografia'] = (int)$categoria['id'] === 5;
        }
        unset($categoria);
        return $categorias;
    }

    public function __construct($db)
    {
        $this->db = $db;
    }

    /** Elige una pregunta activa de la categoría, evitando repetirla en la partida y ajustando la dificultad al jugador. */
    public function getPreguntaAleatoria($partidaId = null, $categoriaId = null, $usuarioId = null)
    {
        // El promedio del jugador usa sus últimas 20 respuestas y arranca en un nivel neutral si todavía no tiene historial.
        $habilidad = 0.5;
        if ($usuarioId !== null) {
            $rendimiento = $this->db->queryOne(
                "SELECT AVG(recientes.correcta) AS ratio FROM (
                    SELECT pp.correcta
                    FROM partida_pregunta pp
                    JOIN partida pa ON pa.id = pp.partida_id
                    WHERE pa.usuario_id = ? AND pp.correcta IS NOT NULL AND pp.opcion_elegida_id IS NOT NULL
                    ORDER BY pp.fecha DESC, pp.id DESC
                ) recientes",
                [(int)$usuarioId]
            );
            if ($rendimiento && $rendimiento['ratio'] !== null) {
                $habilidad = max(0.0, min(1.0, (float)$rendimiento['ratio']));
            }
        }
        // Si el jugador acierta más, buscamos preguntas con una tasa global de aciertos menor.
        $tasaObjetivo = max(0.30, min(0.90, 0.90 - (0.55 * $habilidad)));

        $sql = "SELECT p.id, p.enunciado, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM pregunta p
                JOIN categoria c ON c.id = p.categoria_id
                LEFT JOIN (
                    SELECT pp.pregunta_id, COUNT(*) AS intentos, SUM(pp.correcta = 1) AS aciertos
                    FROM partida_pregunta pp
                    WHERE pp.correcta IS NOT NULL AND pp.opcion_elegida_id IS NOT NULL
                    GROUP BY pp.pregunta_id
                ) dificultad ON dificultad.pregunta_id = p.id
                WHERE p.estado = 'activa'";
        $params = [];

        if ($partidaId !== null) {
            $sql .= " AND p.id NOT IN (SELECT pp.pregunta_id FROM partida_pregunta pp WHERE pp.partida_id = ?)";
            $params[] = $partidaId;
        }

        if ($categoriaId !== null) {
            $sql .= ' AND p.categoria_id = ?';
            $params[] = (int)$categoriaId;
        }

        // Las preguntas nuevas parten de una dificultad media y se ajustan gradualmente con las respuestas reales.
        $sql .= " ORDER BY ABS(
                    ((COALESCE(dificultad.aciertos, 0) + 1.95) / (COALESCE(dificultad.intentos, 0) + 3)) - ?
                 ), RAND() LIMIT 1";
        $params[] = $tasaObjetivo;
        $pregunta = $this->db->queryOne($sql, $params);

        if (!$pregunta) return null;

        $sqlOpciones = "SELECT id, texto, es_correcta FROM opcion WHERE pregunta_id = ? ORDER BY RAND()";
        $pregunta['opciones'] = $this->db->queryAll($sqlOpciones, [$pregunta['id']]);

        return $pregunta;
    }

    /** Devuelve la pregunta con sus opciones (mezcladas), o null si no existe o no esta activa. */
    public function getPreguntaPorId($preguntaId)
    {
        $sql = "SELECT p.id, p.enunciado, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM pregunta p
                JOIN categoria c ON c.id = p.categoria_id
                WHERE p.id = ? AND p.estado = 'activa'";
        $pregunta = $this->db->queryOne($sql, [$preguntaId]);

        if (!$pregunta) return null;

        $sqlOpciones = "SELECT id, texto, es_correcta FROM opcion WHERE pregunta_id = ? ORDER BY RAND()";
        $pregunta['opciones'] = $this->db->queryAll($sqlOpciones, [$pregunta['id']]);

        return $pregunta;
    }

    /** Devuelve id, pregunta_id y es_correcta de una opcion, o null si no existe. */
    public function getOpcion($opcionId)
    {
        return $this->db->queryOne(
            "SELECT id, pregunta_id, es_correcta FROM opcion WHERE id = ?",
            [$opcionId]
        );
    }

    public function esRespuestaCorrecta($opcionId)
    {
        $sql = "SELECT es_correcta FROM opcion WHERE id = ?";
        $resultado = $this->db->queryOne($sql, [$opcionId]);
        return $resultado && (int)$resultado['es_correcta'] === 1;
    }

    public function sumarPunto($usuarioId)
    {
        $sql = "UPDATE usuario SET puntaje_total = puntaje_total + 1 WHERE id = ?";
        $this->db->execute($sql, [$usuarioId]);
    }

    public function getPuntajeTotal($usuarioId)
    {
        $sql = "SELECT puntaje_total FROM usuario WHERE id = ?";
        $resultado = $this->db->queryOne($sql, [$usuarioId]);
        return $resultado ? (int)$resultado['puntaje_total'] : 0;
    }

    public function getRespuestaCorrecta($preguntaId)
    {
        $sql = "SELECT texto FROM opcion WHERE pregunta_id = ? AND es_correcta = 1 LIMIT 1";
        $resultado = $this->db->queryOne($sql, [$preguntaId]);
        return $resultado ? $resultado['texto'] : 'No disponible';
    }

    public function getCategorias()
    {
        return $this->db->queryAll("SELECT id, nombre FROM categoria ORDER BY nombre ASC");
    }

    public function guardarSugerencia($enunciado, $categoriaId, $opciones)
    {
        $sql = "INSERT INTO pregunta (categoria_id, enunciado, estado) VALUES (?, ?, 'pendiente')";
        $this->db->execute($sql, [$categoriaId, $enunciado]);

        $preguntaId = $this->db->lastInsertId();

        foreach ($opciones as $opcion) {
            $sqlOpcion = "INSERT INTO opcion (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)";
            $this->db->execute($sqlOpcion, [$preguntaId, $opcion['texto'], $opcion['es_correcta']]);
        }
    }

    public function guardarReporte($preguntaId, $usuarioId, $motivo)
    {
        $sql = "INSERT INTO reporte_pregunta (pregunta_id, usuario_id, motivo)
                SELECT id, ?, ? FROM pregunta WHERE id = ? AND estado = 'activa'";
        return $this->db->execute($sql, [$usuarioId, $motivo, $preguntaId]);
    }

    public function crearPropuesta($usuarioId, $categoriaId, string $enunciado, array $opciones): void
    {
        $this->db->execute(
            "INSERT INTO pregunta (categoria_id, enunciado, estado, creada_por) VALUES (?, ?, 'pendiente', ?)",
            [$categoriaId, $enunciado, $usuarioId]
        );
        $preguntaId = $this->db->lastInsertId();
        foreach ($opciones as $indice => $opcion) {
            $this->db->execute(
                "INSERT INTO opcion (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)",
                [$preguntaId, $opcion['texto'], (int)$opcion['es_correcta']]
            );
        }
    }

    public function resumenModeracion(): array
    {
        $pendientes = $this->db->queryOne("SELECT COUNT(*) AS total FROM pregunta WHERE estado = 'pendiente'");
        $reportes = $this->db->queryOne("SELECT COUNT(*) AS total FROM reporte_pregunta WHERE resuelto = 0");
        return ['cantidadPendientes' => (int)($pendientes['total'] ?? 0), 'cantidadReportes' => (int)($reportes['total'] ?? 0)];
    }

    public function pendientes(): array
    {
        $preguntas = $this->db->queryAll(
            "SELECT p.id, p.enunciado, p.fecha_creacion, c.nombre AS categoria,
                    COALESCE(u.usuario, 'Sistema') AS autor
             FROM pregunta p JOIN categoria c ON c.id = p.categoria_id
             LEFT JOIN usuario u ON u.id = p.creada_por
             WHERE p.estado = 'pendiente' ORDER BY p.fecha_creacion ASC"
        );
        foreach ($preguntas as &$pregunta) {
            $pregunta['opciones'] = $this->db->queryAll(
                "SELECT texto, es_correcta FROM opcion WHERE pregunta_id = ? ORDER BY id",
                [$pregunta['id']]
            );
        }
        unset($pregunta);
        return $preguntas;
    }

    public function reportesPendientes(): array
    {
        return $this->db->queryAll(
            "SELECT r.id AS reporte_id, r.motivo, r.fecha, p.id AS pregunta_id,
                    p.enunciado, p.estado AS estado_pregunta, c.nombre AS categoria,
                    u.usuario AS reportado_por
             FROM reporte_pregunta r
             JOIN pregunta p ON p.id = r.pregunta_id
             JOIN categoria c ON c.id = p.categoria_id
             JOIN usuario u ON u.id = r.usuario_id
             WHERE r.resuelto = 0 ORDER BY r.fecha ASC"
        );
    }

    public function moderar(int $id, string $accion, string $enunciado = ''): bool
    {
        if ($accion === 'aprobar') {
            return $this->db->execute(
                "UPDATE pregunta SET enunciado = ?, estado = 'activa' WHERE id = ? AND estado = 'pendiente'",
                [$enunciado, $id]
            ) === 1;
        }
        if ($accion === 'rechazar') {
            return $this->db->execute(
                "UPDATE pregunta SET estado = 'baja' WHERE id = ? AND estado = 'pendiente'",
                [$id]
            ) === 1;
        }
        return false;
    }

    public function moderarReporte(int $reporteId, string $accion): bool
    {
        if ($accion === 'retirar') {
            $fila = $this->db->queryOne(
                "SELECT pregunta_id FROM reporte_pregunta WHERE id = ? AND resuelto = 0",
                [$reporteId]
            );
            if (!$fila) return false;
            $this->db->beginTransaction();
            try {
                $this->db->execute("UPDATE pregunta SET estado = 'baja' WHERE id = ?", [$fila['pregunta_id']]);
                $this->db->execute("UPDATE reporte_pregunta SET resuelto = 1 WHERE pregunta_id = ? AND resuelto = 0", [$fila['pregunta_id']]);
                $this->db->commit();
                return true;
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }
        }
        if ($accion === 'descartar') {
            return $this->db->execute(
                "UPDATE reporte_pregunta SET resuelto = 1 WHERE id = ? AND resuelto = 0",
                [$reporteId]
            ) === 1;
        }
        return false;
    }
}
