<?php

namespace app\model;

class RankingModel
{
    // Cada periodo es una ventana movil hasta el momento actual (igual que los filtros del panel admin).
    const PERIODOS = [
        'hoy' => ['etiqueta' => 'Hoy', 'sql' => 'CURDATE()'],
        'semana' => ['etiqueta' => 'Últimos 7 días', 'sql' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)'],
        'mes' => ['etiqueta' => 'Últimos 30 días', 'sql' => 'DATE_SUB(NOW(), INTERVAL 30 DAY)'],
        'total' => ['etiqueta' => 'Total', 'sql' => null],
    ];
    const PERIODO_DEFECTO = 'hoy';

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Valida el periodo recibido por URL: solo se aceptan las claves de PERIODOS.
    public static function periodoValido($periodo): string
    {
        $periodo = is_string($periodo) ? $periodo : '';
        return isset(self::PERIODOS[$periodo]) ? $periodo : self::PERIODO_DEFECTO;
    }

    /**
     * Ranking de jugadores (rol jugador, cuenta activada) por puntos ganados dentro del periodo seleccionado.
     * En empate queda primero el que se registro antes.
     */
    public function getRanking(string $periodo = self::PERIODO_DEFECTO, $limite = 100): array
    {
        $periodo = self::periodoValido($periodo);

        if (self::PERIODOS[$periodo]['sql'] === null) {
            return $this->db->queryAll(
                "SELECT id, usuario, pais, puntaje_total AS puntaje
                 FROM usuario
                 WHERE rol_id = 1 AND cuenta_validada = 1 AND puntaje_total > 0
                 ORDER BY puntaje_total DESC, fecha_registro ASC, id ASC
                 LIMIT " . (int)$limite
            );
        }

        return $this->db->queryAll(
            "SELECT u.id, u.usuario, u.pais, COUNT(*) AS puntaje
             FROM partida_pregunta pp
             JOIN partida pa ON pa.id = pp.partida_id
             JOIN usuario u ON u.id = pa.usuario_id
             WHERE pp.correcta = 1
               AND pp.fecha >= " . self::PERIODOS[$periodo]['sql'] . "
               AND u.rol_id = 1 AND u.cuenta_validada = 1
             GROUP BY u.id, u.usuario, u.pais, u.fecha_registro
             ORDER BY puntaje DESC, u.fecha_registro ASC, u.id ASC
             LIMIT " . (int)$limite
        );
    }
}
