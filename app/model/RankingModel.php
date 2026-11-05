<?php

namespace app\model;

class RankingModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Ranking global por puntaje total acumulado. Solo cuentan jugadores con la cuenta
     * activada y al menos un punto. En empate queda primero el que se registro antes.
     *
     * Cuando se quiera un ranking por periodo (dia, semana, mes...) se puede calcular desde
     * partida_pregunta (correcta = 1 y fecha) o partida (puntaje y fecha_inicio).
     */
    public function getRanking($limite = 100): array
    {
        return $this->db->queryAll(
            "SELECT id, usuario, pais, puntaje_total AS puntaje
             FROM usuario
             WHERE rol_id = 1 AND cuenta_validada = 1 AND puntaje_total > 0
             ORDER BY puntaje_total DESC, fecha_registro ASC, id ASC
             LIMIT " . (int)$limite
        );
    }
}
