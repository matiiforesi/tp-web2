<?php

namespace app\model;

class PreguntaModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getPreguntaAleatoria()
    {
        $sql = "SELECT p.id, p.enunciado, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM pregunta p
                JOIN categoria c ON c.id = p.categoria_id
                WHERE p.estado = 'activa'
                ORDER BY RAND()
                LIMIT 1";
        $pregunta = $this->db->queryOne($sql);

        if (!$pregunta) return null;

        $sqlOpciones = "SELECT id, texto, es_correcta FROM opcion WHERE pregunta_id = ? ORDER BY RAND()";
        $pregunta['opciones'] = $this->db->queryAll($sqlOpciones, [$pregunta['id']]);

        return $pregunta;
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
