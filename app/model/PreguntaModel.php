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

    const NIVELES = ['facil' => 0, 'media' => 1, 'dificil' => 2];
    const UMBRAL_FACIL = 0.70;          // Mas del 70% de aciertos historicos = facil
    const UMBRAL_DIFICIL = 0.30;        // Menos del 30% = dificil; entre ambos, media
    const MIN_INTENTOS_DIFICULTAD = 5;  // Con menos respuestas la pregunta todavia se considera media
    const MIN_RESPUESTAS_JUGADOR = 5;   // Con menos respuestas el jugador se considera de nivel medio
    const VENTANA_JUGADOR = 20;         // Cantidad de partidas usadas para calcular el ratio del jugador

    // Nivel de pregunta correspondiente al jugador segun su ratio de aciertos reciente.
    public function nivelObjetivoJugador($usuarioId): string
    {
        if ($usuarioId === null) return 'media';
        $fila = $this->db->queryOne(
            "SELECT COUNT(*) AS respondidas, AVG(recientes.correcta) AS ratio FROM (
                SELECT pp.correcta
                FROM partida_pregunta pp
                WHERE pp.partida_id IN (SELECT id FROM partida WHERE usuario_id = ?)
                  AND pp.correcta IS NOT NULL AND pp.opcion_elegida_id IS NOT NULL
                ORDER BY pp.fecha DESC, pp.id DESC
                LIMIT " . self::VENTANA_JUGADOR . "
            ) recientes",
            [(int)$usuarioId]
        );
        if (!$fila || (int)$fila['respondidas'] < self::MIN_RESPUESTAS_JUGADOR || $fila['ratio'] === null) {
            return 'media';
        }
        // Un jugador que acierta mucho recibe preguntas dificiles y uno que acierta poco, faciles.
        $ratio = (float)$fila['ratio'];
        return $ratio > self::UMBRAL_FACIL ? 'dificil' : ($ratio < self::UMBRAL_DIFICIL ? 'facil' : 'media');
    }

    private function condicionNoVistas(): string
    {
        return "p.id NOT IN (SELECT pp.pregunta_id FROM partida_pregunta pp
                             WHERE pp.partida_id IN (SELECT pa.id FROM partida pa WHERE pa.usuario_id = ?))";
    }

    // TRUE si el jugador ya vio todas las preguntas activas del sistema (ahí recién se permite repetir).
    public function agotoTodas($usuarioId): bool
    {
        $fila = $this->db->queryOne(
            "SELECT COUNT(*) AS total FROM pregunta p WHERE p.estado = 'activa' AND " . $this->condicionNoVistas(),
            [(int)$usuarioId]
        );
        return (int)($fila['total'] ?? 0) === 0;
    }

    // Categorias que todavia tienen preguntas sin ver para el jugador.
    public function categoriasParaJugador($usuarioId): array
    {
        if ($this->agotoTodas($usuarioId)) {
            return $this->categorias();
        }
        $categorias = $this->db->queryAll(
            "SELECT c.id, c.nombre, c.color FROM categoria c
             WHERE EXISTS (SELECT 1 FROM pregunta p
                           WHERE p.categoria_id = c.id AND p.estado = 'activa' AND " . $this->condicionNoVistas() . ")
             ORDER BY c.nombre",
            [(int)$usuarioId]
        );
        foreach ($categorias as &$categoria) {
            $categoria['esGeografia'] = (int)$categoria['id'] === 5;
        }
        unset($categoria);
        return $categorias;
    }

    /**
     * Elige una pregunta activa de la categoria.
     * - No repite preguntas que el jugador ya vio, salvo que haya visto todas las del sistema
     *   (en ese caso solo evita repetirlas dentro de la partida actual).
     * - Entrega la dificultad que corresponde al jugador; si no hay de ese nivel, la mas cercana.
     */
    public function getPreguntaAleatoria($partidaId = null, $categoriaId = null, $usuarioId = null)
    {
        $nivelObjetivo = self::NIVELES[$this->nivelObjetivoJugador($usuarioId)];

        // Nivel de cada pregunta segun porcentaje historico de aciertos.
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

        if ($usuarioId !== null && !$this->agotoTodas($usuarioId)) {
            $sql .= ' AND ' . $this->condicionNoVistas();
            $params[] = (int)$usuarioId;
        } elseif ($partidaId !== null) {
            $sql .= " AND p.id NOT IN (SELECT pp.pregunta_id FROM partida_pregunta pp WHERE pp.partida_id = ?)";
            $params[] = $partidaId;
        }

        if ($categoriaId !== null) {
            $sql .= ' AND p.categoria_id = ?';
            $params[] = (int)$categoriaId;
        }

        /**
         * 0 = Facil, 1 = Media, 2 = Dificil.
         * Se ordena por cercania al nivel buscado y, dentro del mismo nivel, al azar.
         */
        $sql .= " ORDER BY ABS(CASE
                    WHEN COALESCE(dificultad.intentos, 0) < " . self::MIN_INTENTOS_DIFICULTAD . " THEN 1
                    WHEN dificultad.aciertos / dificultad.intentos > " . self::UMBRAL_FACIL . " THEN 0
                    WHEN dificultad.aciertos / dificultad.intentos < " . self::UMBRAL_DIFICIL . " THEN 2
                    ELSE 1 END - ?), RAND() LIMIT 1";
        $params[] = $nivelObjetivo;
        $pregunta = $this->db->queryOne($sql, $params);

        if (!$pregunta) return null;

        $sqlOpciones = "SELECT id, texto, es_correcta FROM opcion WHERE pregunta_id = ? ORDER BY RAND()";
        $pregunta['opciones'] = $this->db->queryAll($sqlOpciones, [$pregunta['id']]);

        return $pregunta;
    }

    // Devuelve la pregunta con sus opciones (mezcladas), o null si no existe o no esta activa.
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

    // Devuelve id, pregunta_id y es_correcta de una opcion, o null si no existe.
    public function getOpcion($opcionId)
    {
        return $this->db->queryOne(
            "SELECT id, pregunta_id, es_correcta FROM opcion WHERE id = ?",
            [$opcionId]
        );
    }

    public function getPuntajeTotal($usuarioId)
    {
        $sql = "SELECT puntaje_total FROM usuario WHERE id = ?";
        $resultado = $this->db->queryOne($sql, [$usuarioId]);
        return $resultado ? (int)$resultado['puntaje_total'] : 0;
    }

    public function guardarReporte($preguntaId, $usuarioId, $motivo)
    {
        $sql = "INSERT INTO reporte_pregunta (pregunta_id, usuario_id, motivo)
                SELECT p.id, ?, ? FROM pregunta p
                WHERE p.id = ? AND p.estado = 'activa'
                  AND EXISTS (SELECT 1 FROM partida_pregunta pp
                              WHERE pp.pregunta_id = p.id
                                AND pp.partida_id IN (SELECT pa.id FROM partida pa WHERE pa.usuario_id = ?))
                  AND NOT EXISTS (SELECT 1 FROM reporte_pregunta r
                                  WHERE r.pregunta_id = p.id AND r.usuario_id = ? AND r.resuelto = 0)";
        return $this->db->execute($sql, [$usuarioId, $motivo, $preguntaId, $usuarioId, $usuarioId]);
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

    const ESTADOS_EDITABLES = ['activa', 'pendiente', 'baja'];

    public function todasLasCategorias(): array
    {
        return $this->db->queryAll("SELECT id, nombre, color FROM categoria ORDER BY nombre");
    }

    public function categoriaExiste(int $categoriaId): bool
    {
        return $this->db->queryOne("SELECT id FROM categoria WHERE id = ?", [$categoriaId]) !== null;
    }

    public function listarPreguntas(string $texto, int $categoriaId, string $estado, int $pagina, int $porPagina): array
    {
        $condiciones = [];
        $params = [];
        if ($texto !== '') {
            $condiciones[] = "p.enunciado LIKE ?";
            $params[] = '%' . addcslashes($texto, '%_\\') . '%';
        }
        if ($categoriaId > 0) {
            $condiciones[] = "p.categoria_id = ?";
            $params[] = $categoriaId;
        }
        if ($estado !== '') {
            $condiciones[] = "p.estado = ?";
            $params[] = $estado;
        }
        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $total = (int)($this->db->queryOne("SELECT COUNT(*) AS total FROM pregunta p $where", $params)['total'] ?? 0);
        $desde = max(0, ($pagina - 1) * $porPagina);

        $preguntas = $this->db->queryAll(
            "SELECT p.id, p.enunciado, p.estado, p.fecha_creacion, c.nombre AS categoria, c.color AS categoria_color,
                    COALESCE(u.usuario, 'Sistema') AS autor,
                    (SELECT o.texto FROM opcion o WHERE o.pregunta_id = p.id AND o.es_correcta = 1 LIMIT 1) AS correcta,
                    (SELECT COUNT(*) FROM partida_pregunta pp WHERE pp.pregunta_id = p.id AND pp.opcion_elegida_id IS NOT NULL) AS respondidas,
                    (SELECT COUNT(*) FROM partida_pregunta pp WHERE pp.pregunta_id = p.id AND pp.correcta = 1) AS acertadas,
                    (SELECT COUNT(*) FROM reporte_pregunta r WHERE r.pregunta_id = p.id AND r.resuelto = 0) AS reportes
             FROM pregunta p
             JOIN categoria c ON c.id = p.categoria_id
             LEFT JOIN usuario u ON u.id = p.creada_por
             $where
             ORDER BY p.id DESC
             LIMIT " . (int)$porPagina . " OFFSET " . (int)$desde,
            $params
        );
        return ['preguntas' => $preguntas, 'total' => $total];
    }

    public function preguntaParaEditar(int $id): ?array
    {
        $pregunta = $this->db->queryOne(
            "SELECT id, categoria_id, enunciado, estado FROM pregunta WHERE id = ?",
            [$id]
        );
        if (!$pregunta) return null;
        $pregunta['opciones'] = $this->db->queryAll(
            "SELECT o.id, o.texto, o.es_correcta,
                    EXISTS (SELECT 1 FROM partida_pregunta pp WHERE pp.opcion_elegida_id = o.id) AS usada
             FROM opcion o WHERE o.pregunta_id = ? ORDER BY o.id",
            [$id]
        );
        return $pregunta;
    }

    public function crearPregunta(int $editorId, int $categoriaId, string $enunciado, string $estado, array $opciones): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO pregunta (categoria_id, enunciado, estado, creada_por) VALUES (?, ?, ?, ?)",
                [$categoriaId, $enunciado, $estado, $editorId]
            );
            $preguntaId = (int)$this->db->lastInsertId();
            foreach ($opciones as $opcion) {
                $this->db->execute(
                    "INSERT INTO opcion (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)",
                    [$preguntaId, $opcion['texto'], $opcion['es_correcta']]
                );
            }
            $this->db->commit();
            return $preguntaId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizarPregunta(int $id, int $categoriaId, string $enunciado, string $estado, array $opciones, array $opcionesAQuitar): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE pregunta SET categoria_id = ?, enunciado = ?, estado = ? WHERE id = ?",
                [$categoriaId, $enunciado, $estado, $id]
            );
            foreach ($opcionesAQuitar as $opcionId) {
                $this->db->execute("DELETE FROM opcion WHERE id = ? AND pregunta_id = ?", [$opcionId, $id]);
            }
            foreach ($opciones as $opcion) {
                if ($opcion['id'] > 0) {
                    $this->db->execute(
                        "UPDATE opcion SET texto = ?, es_correcta = ? WHERE id = ? AND pregunta_id = ?",
                        [$opcion['texto'], $opcion['es_correcta'], $opcion['id'], $id]
                    );
                } else {
                    $this->db->execute(
                        "INSERT INTO opcion (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)",
                        [$id, $opcion['texto'], $opcion['es_correcta']]
                    );
                }
            }
            if ($estado === 'baja') {
                $this->db->execute("UPDATE reporte_pregunta SET resuelto = 1 WHERE pregunta_id = ? AND resuelto = 0", [$id]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['activa', 'baja'], true)) return false;
        $cambio = $this->db->execute("UPDATE pregunta SET estado = ? WHERE id = ? AND estado <> ?", [$estado, $id, $estado]) === 1;
        if ($cambio && $estado === 'baja') {
            $this->db->execute("UPDATE reporte_pregunta SET resuelto = 1 WHERE pregunta_id = ? AND resuelto = 0", [$id]);
        }
        return $cambio;
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
