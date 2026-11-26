<?php

namespace app\model;

use DateInterval;
use DateTimeImmutable;

// "Jugador" = usuario con rol jugador (1) y cuenta activada. Editores y administradores no cuentan.

class AdminModel
{
    const MIN_RESPUESTAS_ACIERTOS = 5;   // Aciertos por usuario
    const MIN_INTENTOS_PREGUNTA = 3;     // Mas faciles/dificiles
    const TOP_PREGUNTAS = 5;
    const EDAD_MAYOR_DE_EDAD = 18;
    const EDAD_JUBILACION = 65;
    const MAX_PUNTOS_SERIE = ['dia' => 60, 'semana' => 26, 'mes' => 24];

    const PERIODOS = [
        'hoy' => ['etiqueta' => 'Hoy', 'sql' => 'CURDATE()'],
        '7' => ['etiqueta' => 'Últimos 7 días', 'sql' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)'],
        '30' => ['etiqueta' => 'Últimos 30 días', 'sql' => 'DATE_SUB(NOW(), INTERVAL 30 DAY)'],
        'todo' => ['etiqueta' => 'Todo', 'sql' => null],
    ];
    const PERIODO_DEFECTO = 'todo';

    const ETIQUETAS_SEXO = ['M' => 'Masculino', 'F' => 'Femenino', 'N' => 'No especificado'];
    const ETIQUETAS_EDAD = [
        'menores' => 'Menores (< 18)',
        'medio' => 'Medio (18 a 64)',
        'jubilados' => 'Jubilados (65 o más)',
    ];
    const ETIQUETAS_ESTADO_PREGUNTA = [
        'activa' => 'Activas', 'pendiente' => 'Pendientes', 'reportada' => 'Reportadas', 'baja' => 'Dadas de baja',
    ];

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Valida el periodo para que no se pueda romper la pagina.
    public static function periodoValido($periodo): string
    {
        $periodo = is_string($periodo) ? $periodo : '';
        return isset(self::PERIODOS[$periodo]) ? $periodo : self::PERIODO_DEFECTO;
    }

    // Devuelve un pedazo SQL para no tener que escrbir siempre lo mismo en varias consultas a la hora de filtrar.
    private function desde(string $periodo, string $columna): string
    {
        $sql = self::PERIODOS[$periodo]['sql'];
        return $sql === null ? '' : " AND $columna >= $sql";
    }

    /** Obtiene respuestas elegidas por jugadores en un periodo seleccionado.
     * No cuentan las salteadas ni las de tiempo agotado porque no hubo opcion elegida.
     */
    private function respuestas(string $periodo): string
    {
        return "(SELECT pp.pregunta_id, pa.usuario_id, pp.correcta
                 FROM partida_pregunta pp
                 JOIN partida pa ON pa.id = pp.partida_id
                 JOIN usuario u ON u.id = pa.usuario_id
                 WHERE u.rol_id = 1 AND u.cuenta_validada = 1
                   AND pp.correcta IS NOT NULL AND pp.opcion_elegida_id IS NOT NULL"
            . $this->desde($periodo, 'pp.fecha') . ") r";
    }

    // Partidas de jugadores que terminaron o tienen al menos una respuesta (igual que el historial del lobby). */
    private function condicionPartida(): string
    {
        return "u.rol_id = 1
                AND (p.estado = 'finalizada'
                     OR EXISTS (SELECT 1 FROM partida_pregunta px WHERE px.partida_id = p.id))";
    }

    public function metricas(string $periodo): array
    {
        $periodo = self::periodoValido($periodo);
        $cond = $this->condicionPartida();

        $jugadores = (int)($this->db->queryOne("SELECT COUNT(*) AS total FROM usuario WHERE rol_id = 1 AND cuenta_validada = 1")['total'] ?? 0);
        $usuariosNuevos = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS total FROM usuario u
             WHERE u.rol_id = 1 AND u.cuenta_validada = 1" . $this->desde($periodo, 'u.fecha_registro')
        )['total'] ?? 0);

        $partidasTotal = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS total FROM partida p JOIN usuario u ON u.id = p.usuario_id WHERE $cond"
        )['total'] ?? 0);

        $partidasPeriodo = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS total FROM partida p JOIN usuario u ON u.id = p.usuario_id
             WHERE $cond" . $this->desde($periodo, 'p.fecha_inicio')
        )['total'] ?? 0);

        $jugadoresActivos = (int)($this->db->queryOne(
            "SELECT COUNT(DISTINCT p.usuario_id) AS total FROM partida p JOIN usuario u ON u.id = p.usuario_id
             WHERE $cond" . $this->desde($periodo, 'p.fecha_inicio')
        )['total'] ?? 0);

        // Preguntas mostradas en el periodo selecionado (todas).
        $mostradas = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS total
             FROM partida_pregunta pp
             JOIN partida p ON p.id = pp.partida_id
             JOIN usuario u ON u.id = p.usuario_id
             WHERE u.rol_id = 1" . $this->desde($periodo, 'pp.fecha')
        )['total'] ?? 0);

        // Promedio de preguntas por partida que EMPEZARON en el periodo seleccionado, asi una partida que cruza la medianoche no distorsiona el cociente.
        $preguntasDePartidas = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS total
             FROM partida_pregunta pp
             JOIN partida p ON p.id = pp.partida_id
             JOIN usuario u ON u.id = p.usuario_id
             WHERE $cond" . $this->desde($periodo, 'p.fecha_inicio')
        )['total'] ?? 0);

        $aciertos = $this->db->queryOne(
            "SELECT COUNT(*) AS respondidas, SUM(r.correcta = 1) AS correctas FROM " . $this->respuestas($periodo)
        );
        $respondidas = (int)($aciertos['respondidas'] ?? 0);
        $correctas = (int)($aciertos['correctas'] ?? 0);

        // Distingue si una pregunta la cargo el sistema o un usuario habilitado por rol. Si se borra al autor, pasa a ser por sistema.
        $cargadas = $this->db->queryOne(
            "SELECT COUNT(*) AS total,
                    SUM(p.creada_por IS NULL) AS sistema,
                    SUM(u.rol_id = 1) AS jugadores,
                    SUM(u.rol_id IN (2, 3)) AS editores
             FROM pregunta p LEFT JOIN usuario u ON u.id = p.creada_por
             WHERE 1 = 1" . $this->desde($periodo, 'p.fecha_creacion')
        );

        $porEstado = array_fill_keys(array_keys(self::ETIQUETAS_ESTADO_PREGUNTA), 0);
        foreach ($this->db->queryAll("SELECT estado, COUNT(*) AS total FROM pregunta GROUP BY estado") as $fila) {
            $porEstado[$fila['estado']] = (int)$fila['total'];
        }

        $reportesPendientes = (int)($this->db->queryOne("SELECT COUNT(*) AS total FROM reporte_pregunta WHERE resuelto = 0")['total'] ?? 0);
        $reportesNuevos = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS total FROM reporte_pregunta WHERE 1 = 1" . $this->desde($periodo, 'fecha')
        )['total'] ?? 0);

        $partidas = (int)$partidasPeriodo;
        return [
            'jugadores' => $jugadores,
            'jugadoresActivos' => $jugadoresActivos,
            'usuariosNuevos' => $usuariosNuevos,
            'partidasTotal' => $partidasTotal,
            'partidasPeriodo' => $partidas,
            'preguntasMostradas' => $mostradas,
            'promedioPreguntasPorPartida' => $partidas > 0 ? round($preguntasDePartidas / $partidas, 1) : 0.0,
            'respondidas' => $respondidas,
            'correctas' => $correctas,
            'aciertoGlobal' => $respondidas > 0 ? round($correctas * 100 / $respondidas, 1) : null,
            'cargadasPeriodo' => (int)($cargadas['total'] ?? 0),
            'origenSistema' => (int)($cargadas['sistema'] ?? 0),
            'origenJugadores' => (int)($cargadas['jugadores'] ?? 0),
            'origenEditores' => (int)($cargadas['editores'] ?? 0),
            'preguntasPorEstado' => $porEstado,
            'preguntasTotal' => array_sum($porEstado),
            'reportesPendientes' => $reportesPendientes,
            'reportesNuevos' => $reportesNuevos,
        ];
    }

    // Porcentaje de aciertos por jugador. Minimo de respuestas: 5
    public function aciertosPorUsuario(string $periodo, int $limite = 100): array
    {
        $periodo = self::periodoValido($periodo);
        $minimo = self::MIN_RESPUESTAS_ACIERTOS;
        $filas = $this->db->queryAll(
            "SELECT u.usuario, u.pais, COUNT(*) AS respondidas, SUM(r.correcta = 1) AS correctas
             FROM " . $this->respuestas($periodo) . "
             JOIN usuario u ON u.id = r.usuario_id
             GROUP BY u.id, u.usuario, u.pais
             HAVING COUNT(*) >= $minimo
             ORDER BY (SUM(r.correcta = 1) / COUNT(*)) DESC, COUNT(*) DESC, u.usuario ASC
             LIMIT " . (int)$limite
        );

        return array_map(static function ($fila) {
            $respondidas = (int)$fila['respondidas'];
            $correctas = (int)$fila['correctas'];
            return [
                'usuario' => $fila['usuario'],
                'pais' => $fila['pais'],
                'respondidas' => $respondidas,
                'correctas' => $correctas,
                'porcentaje' => $respondidas > 0 ? round($correctas * 100 / $respondidas, 1) : 0.0,
            ];
        }, $filas);
    }

    // Acietos por categoria (null si no hubo respuestas).
    public function aciertosPorCategoria(string $periodo): array
    {
        $periodo = self::periodoValido($periodo);
        $filas = $this->db->queryAll(
            "SELECT c.nombre, COUNT(r.pregunta_id) AS respondidas, SUM(r.correcta = 1) AS correctas
             FROM categoria c
             LEFT JOIN pregunta p ON p.categoria_id = c.id
             LEFT JOIN " . $this->respuestas($periodo) . " ON r.pregunta_id = p.id
             GROUP BY c.id, c.nombre
             ORDER BY c.nombre ASC"
        );

        return array_map(static function ($fila) {
            $respondidas = (int)$fila['respondidas'];
            $correctas = (int)$fila['correctas'];
            return [
                'categoria' => $fila['nombre'],
                'respondidas' => $respondidas,
                'correctas' => $correctas,
                'porcentaje' => $respondidas > 0 ? round($correctas * 100 / $respondidas, 1) : null,
            ];
        }, $filas);
    }

    // Preguntas con menor y mayor porcentaje de acierto. Minimo de intentos: 3
    public function dificultadPreguntas(string $periodo): array
    {
        $periodo = self::periodoValido($periodo);
        $minimo = self::MIN_INTENTOS_PREGUNTA;
        $filas = $this->db->queryAll(
            "SELECT p.id, p.enunciado, c.nombre AS categoria, COUNT(*) AS intentos, SUM(r.correcta = 1) AS correctas
             FROM " . $this->respuestas($periodo) . "
             JOIN pregunta p ON p.id = r.pregunta_id
             JOIN categoria c ON c.id = p.categoria_id
             GROUP BY p.id, p.enunciado, c.nombre
             HAVING COUNT(*) >= $minimo"
        );

        $preguntas = array_map(static function ($fila) {
            $intentos = (int)$fila['intentos'];
            $correctas = (int)$fila['correctas'];
            return [
                'id' => (int)$fila['id'],
                'enunciado' => $fila['enunciado'],
                'categoria' => $fila['categoria'],
                'intentos' => $intentos,
                'porcentaje' => round($correctas * 100 / $intentos, 1),
            ];
        }, $filas);

        return self::separarDificultad($preguntas, self::TOP_PREGUNTAS);
    }

    // Recibe la lista de preguntas con porcentaje de acierto y cantidad de intentos y las ordena de menor a mayor.
    public static function separarDificultad(array $preguntas, int $top): array
    {
        usort($preguntas, static function ($a, $b) {
            return [$a['porcentaje'], $b['intentos'], $a['id']] <=> [$b['porcentaje'], $a['intentos'], $b['id']];
        });
        $dificiles = array_slice($preguntas, 0, min($top, (int)ceil(count($preguntas) / 2)));
        $idsDificiles = array_column($dificiles, 'id');

        $resto = array_values(array_filter($preguntas, static function ($p) use ($idsDificiles) {
            return !in_array($p['id'], $idsDificiles, true);
        }));
        usort($resto, static function ($a, $b) {
            return [$b['porcentaje'], $b['intentos'], $a['id']] <=> [$a['porcentaje'], $a['intentos'], $b['id']];
        });

        return ['dificiles' => $dificiles, 'faciles' => array_slice($resto, 0, $top)];
    }

    // Partidas por dia, semana y mes.
    public function partidasPorTiempo(string $periodo): array
    {
        $periodo = self::periodoValido($periodo);
        $cond = $this->condicionPartida();
        $filtro = $this->desde($periodo, 'p.fecha_inicio');

        $expresiones = [
            'dia' => 'DATE(p.fecha_inicio)',
            'semana' => 'DATE_SUB(DATE(p.fecha_inicio), INTERVAL WEEKDAY(p.fecha_inicio) DAY)',
            'mes' => "DATE_FORMAT(p.fecha_inicio, '%Y-%m-01')",
        ];

        // Fechas de referencia tomadas de la base, para no mezclar la zona horaria de PHP con la de MySQL.
        $hoy = (string)$this->db->queryOne("SELECT CURDATE() AS d")['d'];
        $desde = null;
        if (self::PERIODOS[$periodo]['sql'] !== null) {
            $desde = (string)$this->db->queryOne("SELECT DATE(" . self::PERIODOS[$periodo]['sql'] . ") AS d")['d'];
        }

        $series = [];
        foreach ($expresiones as $granularidad => $expresion) {
            $filas = $this->db->queryAll(
                "SELECT $expresion AS clave, COUNT(*) AS total
                 FROM partida p JOIN usuario u ON u.id = p.usuario_id
                 WHERE $cond $filtro
                 GROUP BY clave
                 ORDER BY clave ASC"
            );
            $conteos = [];
            foreach ($filas as $fila) {
                $conteos[(string)$fila['clave']] = (int)$fila['total'];
            }

            $inicio = $desde ?? (empty($conteos) ? null : array_key_first($conteos));
            $series[$granularidad] = $inicio === null
                ? ['labels' => [], 'valores' => []]
                : self::completarSerie($conteos, $inicio, $hoy, $granularidad, self::MAX_PUNTOS_SERIE[$granularidad]);
        }
        return $series;
    }

    // Completa con 0 los dias que no se jugaron partidas para que no haya dias salteados.
    public static function completarSerie(array $conteos, string $desde, string $hasta, string $granularidad, int $maximo): array
    {
        $fin = new DateTimeImmutable($hasta);
        $actual = new DateTimeImmutable($desde);

        if ($granularidad === 'semana') {
            $actual = $actual->modify('-' . ((int)$actual->format('N') - 1) . ' days'); // lunes de esa semana
            $paso = new DateInterval('P7D');
            $formato = 'd/m';
        } elseif ($granularidad === 'mes') {
            $actual = $actual->modify('first day of this month');
            $paso = new DateInterval('P1M');
            $formato = 'm/Y';
        } else {
            $paso = new DateInterval('P1D');
            $formato = 'd/m';
        }

        $labels = [];
        $valores = [];
        while ($actual <= $fin) {
            $labels[] = ($granularidad === 'semana' ? 'Sem. ' : '') . $actual->format($formato);
            $valores[] = $conteos[$actual->format('Y-m-d')] ?? 0;
            $actual = $actual->add($paso);
        }

        return [
            'labels' => array_slice($labels, -$maximo),
            'valores' => array_slice($valores, -$maximo),
        ];
    }

    public function usuariosPorPais(string $periodo): array
    {
        $periodo = self::periodoValido($periodo);
        return $this->db->queryAll(
            "SELECT u.pais, COUNT(*) AS total
             FROM usuario u
             WHERE u.rol_id = 1 AND u.cuenta_validada = 1" . $this->desde($periodo, 'u.fecha_registro') . "
             GROUP BY u.pais_codigo, u.pais
             ORDER BY total DESC, u.pais ASC"
        );
    }

    public function usuariosPorSexo(string $periodo): array
    {
        $periodo = self::periodoValido($periodo);
        $totales = array_fill_keys(array_keys(self::ETIQUETAS_SEXO), 0);
        $filas = $this->db->queryAll(
            "SELECT u.sexo, COUNT(*) AS total FROM usuario u
             WHERE u.rol_id = 1 AND u.cuenta_validada = 1" . $this->desde($periodo, 'u.fecha_registro') . "
             GROUP BY u.sexo"
        );
        foreach ($filas as $fila) {
            if (isset($totales[$fila['sexo']])) {
                $totales[$fila['sexo']] = (int)$fila['total'];
            }
        }
        return $totales;
    }

    // Menores (< 18), Medio (18 a 64) y Jubilados (65 <).
    public function usuariosPorEdad(string $periodo): array
    {
        $periodo = self::periodoValido($periodo);
        $totales = array_fill_keys(array_keys(self::ETIQUETAS_EDAD), 0);
        $mayor = self::EDAD_MAYOR_DE_EDAD;
        $jubilacion = self::EDAD_JUBILACION;
        $filas = $this->db->queryAll(
            "SELECT CASE
                        WHEN TIMESTAMPDIFF(YEAR, u.fecha_nacimiento, CURDATE()) < $mayor THEN 'menores'
                        WHEN TIMESTAMPDIFF(YEAR, u.fecha_nacimiento, CURDATE()) < $jubilacion THEN 'medio'
                        ELSE 'jubilados'
                    END AS grupo,
                    COUNT(*) AS total
             FROM usuario u
             WHERE u.rol_id = 1 AND u.cuenta_validada = 1" . $this->desde($periodo, 'u.fecha_registro') . "
             GROUP BY grupo"
        );
        foreach ($filas as $fila) {
            if (isset($totales[$fila['grupo']])) {
                $totales[$fila['grupo']] = (int)$fila['total'];
            }
        }
        return $totales;
    }
}
