<?php

namespace app\model;

class PreguntaModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Elige una pregunta activa al azar. Si se indica $partidaId, excluye las que ya se
     * respondieron en esa partida (dentro de una misma partida no se repite ninguna).
     * Devuelve null si no queda ninguna disponible.
     */
    public function getPreguntaAleatoria($partidaId = null)
    {
        $sql = "SELECT p.id, p.enunciado, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM pregunta p
                JOIN categoria c ON c.id = p.categoria_id
                WHERE p.estado = 'activa'";
        $params = [];

        if ($partidaId !== null) {
            $sql .= " AND p.id NOT IN (SELECT pp.pregunta_id FROM partida_pregunta pp WHERE pp.partida_id = ?)";
            $params[] = $partidaId;
        }

        $sql .= " ORDER BY RAND() LIMIT 1";
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
}
