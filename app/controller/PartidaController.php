<?php

namespace app\controller;

use app\model\PartidaModel;

class PartidaController
{
    private $model;     // PreguntaModel
    private $partidas;  // PartidaModel
    private $render;

    public function __construct($model, $partidas, $render)
    {
        $this->model = $model;
        $this->partidas = $partidas;
        $this->render = $render;
    }

    // GET /partida/nueva
    public function nueva()
    {
        $usuarioId = $this->exigirLogin();
        $this->ignorarPrecarga();
        $recursos = $this->partidas->recursosUsuario($usuarioId);
        if ($recursos['vidas'] <= 0) {
            $_SESSION['aviso_lobby'] = 'No tenés vidas disponibles. Recuperás una vida automáticamente cada ' . PartidaModel::MINUTOS_POR_VIDA . ' minutos.';
            \Redirect::to('/usuario/lobby');
        }
        $_SESSION['vidas'] = $recursos['vidas'];

        $this->partidas->cerrarEnCurso($usuarioId);
        $_SESSION['partida_id'] = $this->partidas->crear($usuarioId);
        $_SESSION['vidas'] = $recursos['vidas'];
        $_SESSION['racha'] = 0;
        unset($_SESSION['categoria_ruleta']);

        \Redirect::to('/partida/jugar');
    }

    // GET /partida/jugar
    public function jugar()
    {
        $usuarioId = $this->exigirLogin();
        $this->ignorarPrecarga();
        $recursos = $this->partidas->recursosUsuario($usuarioId);
        if ($recursos['vidas'] <= 0) {
            $_SESSION['aviso_lobby'] = 'No tenés vidas disponibles. Esperá la recuperación automática para volver a jugar.';
            \Redirect::to('/usuario/lobby');
        }
        $_SESSION['vidas'] = $recursos['vidas'];

        // Una partida nueva solo se crea desde el boton "Jugar".
        $partidaId = (int)($_SESSION['partida_id'] ?? 0);
        if ($partidaId === 0 || !$this->partidas->estaEnCurso($partidaId, $usuarioId)) {
            \Redirect::to('/usuario/lobby');
        }

        // Si el jugador deja una pregunta sin responder, recarga o vuelve atrás, la partida termina.
        if ($this->partidas->preguntaPendiente($partidaId) !== null) {
            $this->partidas->cerrarEnCurso($usuarioId);
            unset($_SESSION['partida_id']);
            $_SESSION['aviso_lobby'] = PartidaModel::AVISO_ABANDONO;
            \Redirect::to('/usuario/lobby');
        }

        if (!isset($_SESSION['categoria_ruleta'])) {
            $categorias = $this->model->categorias();
            $segmentos = [];
            $totalCategorias = min(5, count($categorias));
            foreach (array_slice($categorias, 0, $totalCategorias) as $indice => $categoria) {
                $inicio = (int)round($indice * 100 / max(1, $totalCategorias));
                $fin = (int)round(($indice + 1) * 100 / max(1, $totalCategorias));
                $color = preg_match('/^#[0-9A-Fa-f]{6}$/', $categoria['color'] ?? '') ? $categoria['color'] : '#243b53';
                $segmentos[] = $color . ' ' . $inicio . '% ' . $fin . '%';
            }
            $this->render->renderiza('ruleta', [
                'categorias' => $categorias,
                'vidas' => $recursos['vidas'],
                'proximaVidaSegundos' => $recursos['proxima_vida_segundos'],
                'ruletaColores' => implode(', ', $segmentos),
            ]);
            return;
        }

        // No se repiten preguntas dentro de una misma partida.
        $pregunta = $this->model->getPreguntaAleatoria($partidaId, (int)$_SESSION['categoria_ruleta'], $usuarioId);

        if ($pregunta === null) {
            unset($_SESSION['categoria_ruleta']);
            if ($this->partidas->cantidadRespuestas($partidaId) > 0) {
                \Redirect::to('/partida/jugar');
            }
            // La partida termina cuando ya no quedan preguntas disponibles.
            $completada = $this->partidas->cantidadRespuestas($partidaId) > 0;
            if ($completada) {
                $this->partidas->cerrarEnCurso($usuarioId);
                unset($_SESSION['partida_id']);
            }
            $this->render->renderiza('juegoVacio', ['completada' => $completada]);
            return;
        }

        $this->partidas->marcarMostrada($partidaId, (int)$pregunta['id']);
        unset($_SESSION['categoria_ruleta']);
        $_SESSION['orden_opciones'] = array_map('intval', array_column($pregunta['opciones'], 'id'));
        unset($_SESSION['resultado']);

        // La pregunta no se guarda en caché para detectar recargas y vueltas atrás como abandono.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $this->render->renderiza('partida', [
            'pregunta' => $pregunta,
            'puntajeTotal' => $this->model->getPuntajeTotal($usuarioId),
            'jugando' => true,
            'segundos' => PartidaModel::SEGUNDOS_POR_PREGUNTA,
            'vidas' => $recursos['vidas'],
            'proximaVidaSegundos' => $recursos['proxima_vida_segundos'],
            'dinero' => $recursos['dinero'],
            'estrategias' => true,
        ]);
    }

    // POST /partida/estrategia: compra y activa una ayuda en la pregunta actual.
    public function estrategia()
    {
        $usuarioId = $this->exigirLogin();
        header('Content-Type: application/json; charset=utf-8');
        $partidaId = (int)($_SESSION['partida_id'] ?? 0);
        $pendiente = $partidaId ? $this->partidas->preguntaPendiente($partidaId) : null;
        $accion = (string)\Request::post('accion');
        $costos = ['bomba' => 20, 'saltear' => 80, 'escudo' => 60, 'tiempo' => 25];
        if (!$partidaId || !$this->partidas->estaEnCurso($partidaId, $usuarioId) || !$pendiente || !isset($costos[$accion])) {
            http_response_code(400);
            echo json_encode(['error' => 'La estrategia no está disponible ahora.']);
            return;
        }
        $preguntaId = (int)$pendiente['pregunta_id'];
        $clave = $partidaId . ':' . $preguntaId;
        if (!empty($_SESSION['estrategias_usadas'][$clave][$accion])) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya usaste esta estrategia en esta pregunta.']);
            return;
        }
        if ($accion === 'saltear') {
            if (!$this->partidas->canjearEstrategia($usuarioId, $costos[$accion])) {
                http_response_code(402); echo json_encode(['error' => 'No tenés monedas suficientes.']); return;
            }
            if (!$this->partidas->registrarSalto($partidaId, $preguntaId)) {
                $this->partidas->acreditarDinero($usuarioId, $costos[$accion]);
                http_response_code(409); echo json_encode(['error' => 'La pregunta ya fue respondida.']); return;
            }
            $_SESSION['estrategias_usadas'][$clave][$accion] = true;
            $_SESSION['racha'] = 0;
            unset($_SESSION['categoria_ruleta']);
            echo json_encode(['redirect' => '/partida/jugar']);
            return;
        }
        if (!$this->partidas->canjearEstrategia($usuarioId, $costos[$accion])) {
            http_response_code(402); echo json_encode(['error' => 'No tenés monedas suficientes.']); return;
        }
        $_SESSION['estrategias_usadas'][$clave][$accion] = true;
        if ($accion === 'bomba') {
            $pregunta = $this->model->getPreguntaPorId($preguntaId);
            $incorrectas = array_values(array_map(static fn($opcion) => (int)$opcion['id'], array_filter($pregunta['opciones'] ?? [], static fn($opcion) => (int)$opcion['es_correcta'] !== 1)));
            shuffle($incorrectas);
            echo json_encode(['eliminar' => array_slice($incorrectas, 0, 2), 'dinero' => $this->partidas->recursosUsuario($usuarioId)['dinero']]);
            return;
        }
        if ($accion === 'escudo') {
            $_SESSION['escudo_pregunta'] = $clave;
        } else {
            $_SESSION['tiempo_extra_pregunta'] = $clave;
        }
        echo json_encode(['ok' => true, 'accion' => $accion, 'dinero' => $this->partidas->recursosUsuario($usuarioId)['dinero']]);
    }

    // POST /partida/seleccionarCategoria: la ruleta elige una categoria activa.
    public function seleccionarCategoria()
    {
        $usuarioId = $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            \Redirect::to('/partida/jugar');
        }
        $partidaId = (int)($_SESSION['partida_id'] ?? 0);
        if ($partidaId === 0 || !$this->partidas->estaEnCurso($partidaId, $usuarioId)) {
            \Redirect::to('/usuario/lobby');
        }
        $categoriaId = filter_var(\Request::post('categoria_id'), FILTER_VALIDATE_INT);
        $categorias = $this->model->categorias();
        $permitida = array_filter($categorias, static fn($categoria) => (int)$categoria['id'] === (int)$categoriaId);
        if (!$permitida) {
            \Redirect::to('/partida/jugar');
        }
        $_SESSION['categoria_ruleta'] = (int)$categoriaId;
        \Redirect::to('/partida/jugar');
    }

    // POST /partida/responder
    public function responder()
    {
        $usuarioId = $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            \Redirect::to('/partida/jugar');
        }

        $partidaId = (int)($_SESSION['partida_id'] ?? 0);
        if ($partidaId === 0 || !$this->partidas->estaEnCurso($partidaId, $usuarioId)) {
            \Redirect::to('/usuario/lobby');
        }

        $opcionId = filter_var(\Request::post('opcion_id'), FILTER_VALIDATE_INT);
        $pendiente = $this->partidas->preguntaPendiente($partidaId);

        if ($pendiente === null) {
            http_response_code(204);
            return;
        }

        $preguntaId = (int)$pendiente['pregunta_id'];
        $opcion = ($opcionId === false || $opcionId === null) ? null : $this->model->getOpcion($opcionId);

        if ($opcion !== null && (int)$opcion['pregunta_id'] !== $preguntaId) {
            http_response_code(204);
            return;
        }

        $claveEstrategia = $partidaId . ':' . $preguntaId;
        $tiempoExtra = (($_SESSION['tiempo_extra_pregunta'] ?? null) === $claveEstrategia) ? 15 : 0;
        $limite = PartidaModel::SEGUNDOS_POR_PREGUNTA + $tiempoExtra + PartidaModel::MARGEN_SEGUNDOS;
        $tiempoAgotado = (int)$pendiente['segundos'] > $limite;
        $elegidaId = ($opcion !== null && !$tiempoAgotado) ? (int)$opcion['id'] : null;
        $correcta = $elegidaId !== null && (int)$opcion['es_correcta'] === 1;

        $registrada = $this->partidas->registrarRespuesta(
            $partidaId, $usuarioId, $preguntaId, $elegidaId, $correcta,
            (($_SESSION['escudo_pregunta'] ?? null) === $claveEstrategia && !$correcta)
        );

        if (!$registrada) {
            http_response_code(204);
            return;
        }

        $escudoUsado = (($_SESSION['escudo_pregunta'] ?? null) === $claveEstrategia && !$correcta);
        unset($_SESSION['escudo_pregunta'], $_SESSION['tiempo_extra_pregunta']);

        $recursosActualizados = $this->partidas->recursosUsuario($usuarioId);
        $_SESSION['vidas'] = $recursosActualizados['vidas'];

        $_SESSION['resultado'] = [
            'partida_id' => $partidaId,
            'pregunta_id' => $preguntaId,
            'elegida_id' => $elegidaId,
            'correcta' => $correcta,
            'escudoUsado' => $escudoUsado,
        ];

        if (!$correcta) {
            $_SESSION['racha'] = 0;
        }
        $bonificacion = 0;
        if ($correcta) {
            $_SESSION['racha'] = (int)($_SESSION['racha'] ?? 0) + 1;
            $racha = $_SESSION['racha'];
            if ($racha >= 3 && $racha % 2 === 1) {
                $bonificacion = 10;
                $this->partidas->acreditarDinero($usuarioId, $bonificacion);
            }
        }
        if (!$correcta && (int)$_SESSION['vidas'] <= 0) {
            $this->partidas->finalizar($partidaId, $usuarioId);
            unset($_SESSION['partida_id']);
        }

        $_SESSION['resultado']['racha'] = (int)($_SESSION['racha'] ?? 0);
        $_SESSION['resultado']['bonificacion'] = $bonificacion;

        \Redirect::to('/partida/resultado');
    }

    // GET /partida/resultado
    public function resultado()
    {
        $usuarioId = $this->exigirLogin();
        $recursos = $this->partidas->recursosUsuario($usuarioId);
        $_SESSION['vidas'] = $recursos['vidas'];

        $resultado = $_SESSION['resultado'] ?? null;
        if ($resultado === null) {
            \Redirect::to('/usuario/lobby');
        }

        $sinVidas = !$resultado['correcta'] && (int)($_SESSION['vidas'] ?? 0) <= 0;
        $siguiente = $resultado['correcta'] || !$sinVidas ? '/partida/jugar' : '/partida/fin';
        $pregunta = $this->model->getPreguntaPorId($resultado['pregunta_id']);
        if ($pregunta === null) {
            \Redirect::to($siguiente);
        }

        $orden = array_flip($_SESSION['orden_opciones'] ?? []);
        usort($pregunta['opciones'], function ($a, $b) use ($orden) {
            return ($orden[(int)$a['id']] ?? 0) <=> ($orden[(int)$b['id']] ?? 0);
        });

        foreach ($pregunta['opciones'] as &$opcion) {
            if ((int)$opcion['es_correcta'] === 1) {
                $opcion['clase'] = 'opcion-correcta';
            } elseif ((int)$opcion['id'] === $resultado['elegida_id']) {
                $opcion['clase'] = 'opcion-incorrecta';
            } else {
                $opcion['clase'] = 'opcion-apagada';
            }
        }
        unset($opcion);

        if ($resultado['correcta']) {
            $mensaje = '¡Correcto! +1 punto';
        } elseif ($resultado['elegida_id'] === null) {
            $mensaje = '¡Se acabó el tiempo!';
        } else {
            $mensaje = !empty($resultado['escudoUsado']) ? 'Respuesta incorrecta: el escudo protegió tu vida.' : 'Respuesta incorrecta';
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $this->render->renderiza('partida', [
            'pregunta' => $pregunta,
            'puntajeTotal' => $this->model->getPuntajeTotal($usuarioId),
            'jugando' => false,
            'mensaje' => $mensaje,
            'claseMensaje' => $resultado['correcta'] ? 'resultado-ok' : 'resultado-mal',
            'siguiente' => $siguiente,
            'textoSiguiente' => $resultado['correcta'] ? 'Siguiente pregunta' : 'Ver resultado',
            'avanzarSolo' => !$sinVidas && !$resultado['correcta'] && $resultado['elegida_id'] !== null,
            'vidas' => $recursos['vidas'],
            'sinVidas' => $sinVidas,
            'racha' => (int)($resultado['racha'] ?? 0),
            'bonificacion' => (int)($resultado['bonificacion'] ?? 0),
            'avisoReporte' => $_SESSION['aviso_reporte'] ?? null,
        ]);
        unset($_SESSION['aviso_reporte']);
    }

    // GET /partida/fin
    public function fin()
    {
        $usuarioId = $this->exigirLogin();
        $this->ignorarPrecarga();
        $recursos = $this->partidas->recursosUsuario($usuarioId);
        $_SESSION['vidas'] = $recursos['vidas'];

        $resultado = $_SESSION['resultado'] ?? null;
        if ($resultado === null || $resultado['correcta']) {
            \Redirect::to('/usuario/lobby');
        }

        unset($_SESSION['resultado'], $_SESSION['orden_opciones']);

        $this->mostrarFinDePartida($usuarioId, $resultado['partida_id'], false, $resultado['pregunta_id']);
    }

    // Ignora las precargas del navegador para no iniciar partidas ni mostrar preguntas por accidente.
    private function ignorarPrecarga(): void
    {
        if (!\Request::esNavegacion()) {
            http_response_code(204);
            exit;
        }
    }

    private function mostrarFinDePartida($usuarioId, $partidaId, $fueCorrecta, $preguntaId = null)
    {
        $puntosPartida = $this->partidas->getPuntajePartida($partidaId);

        $textoCorrecto = null;
        if (!$fueCorrecta && $preguntaId !== null) {
            $textoCorrecto = $this->model->getRespuestaCorrecta($preguntaId);
        }

        $this->render->renderiza('finPartida', [
            'fueCorrecta' => $fueCorrecta,
            'puntosGanados' => $puntosPartida,
            'respuestaCorrecta' => $textoCorrecto,
            'vidas' => $recursos['vidas']
        ]);
    }

    private function exigirLogin(): int
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }
        return (int)$_SESSION['usuario_id'];
    }
}
