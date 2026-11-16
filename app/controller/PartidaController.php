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

        $this->partidas->cerrarEnCurso($usuarioId);
        $_SESSION['partida_id'] = $this->partidas->crear($usuarioId);

        \Redirect::to('/partida/jugar');
    }

    // GET /partida/jugar
    public function jugar()
    {
        $usuarioId = $this->exigirLogin();
        $this->ignorarPrecarga();

        // Una partida nueva solo se crea desde el boton "Jugar".
        $partidaId = (int)($_SESSION['partida_id'] ?? 0);
        if ($partidaId === 0 || !$this->partidas->estaEnCurso($partidaId, $usuarioId)) {
            \Redirect::to('/usuario/lobby');
        }

        // Cada pregunta se muestra una sola vez. Si se mostro y no respondio, el
        // jugador recargo, volvio atras o abrio otra pestana: la partida termina.
        if ($this->partidas->preguntaPendiente($partidaId) !== null) {
            $this->partidas->cerrarEnCurso($usuarioId);
            unset($_SESSION['partida_id']);
            $_SESSION['aviso_lobby'] = PartidaModel::AVISO_ABANDONO;
            \Redirect::to('/usuario/lobby');
        }

        // No se repiten preguntas dentro de una misma partida.
        $pregunta = $this->model->getPreguntaAleatoria($partidaId);

        if ($pregunta === null) {
            // Si ya contesto todas preguntas disponibles en una partida: la partida termina.
            $completada = $this->partidas->cantidadRespuestas($partidaId) > 0;
            if ($completada) {
                $this->partidas->cerrarEnCurso($usuarioId);
                unset($_SESSION['partida_id']);
            }
            $this->render->renderiza('juegoVacio', ['completada' => $completada]);
            return;
        }

        $this->partidas->marcarMostrada($partidaId, (int)$pregunta['id']);
        $_SESSION['orden_opciones'] = array_map('intval', array_column($pregunta['opciones'], 'id'));
        unset($_SESSION['resultado']);

        // La pagina con la pregunta no se guarda en cache. Esto hace que
        // el servidor detecte como abandono el volver atras o recargar.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $this->render->renderiza('partida', [
            'pregunta' => $pregunta,
            'puntajeTotal' => $this->model->getPuntajeTotal($usuarioId),
            'jugando' => true,
            'segundos' => PartidaModel::SEGUNDOS_POR_PREGUNTA,
        ]);
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

        $limite = PartidaModel::SEGUNDOS_POR_PREGUNTA + PartidaModel::MARGEN_SEGUNDOS;
        $tiempoAgotado = (int)$pendiente['segundos'] > $limite;
        $elegidaId = ($opcion !== null && !$tiempoAgotado) ? (int)$opcion['id'] : null;
        $correcta = $elegidaId !== null && (int)$opcion['es_correcta'] === 1;

        $registrada = $this->partidas->registrarRespuesta(
            $partidaId, $usuarioId, $preguntaId, $elegidaId, $correcta
        );

        if (!$registrada) {
            http_response_code(204);
            return;
        }

        $_SESSION['resultado'] = [
            'partida_id' => $partidaId,
            'pregunta_id' => $preguntaId,
            'elegida_id' => $elegidaId,
            'correcta' => $correcta,
        ];

        if (!$correcta) {
            unset($_SESSION['partida_id']);
        }

        \Redirect::to('/partida/resultado');
    }

    // GET /partida/resultado
    public function resultado()
    {
        $usuarioId = $this->exigirLogin();

        $resultado = $_SESSION['resultado'] ?? null;
        if ($resultado === null) {
            \Redirect::to('/usuario/lobby');
        }

        $siguiente = $resultado['correcta'] ? '/partida/jugar' : '/partida/fin';
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
            $mensaje = 'Respuesta incorrecta';
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
            'avanzarSolo' => !$resultado['correcta'] && $resultado['elegida_id'] !== null,
        ]);
    }

    // GET /partida/fin
    public function fin()
    {
        $usuarioId = $this->exigirLogin();
        $this->ignorarPrecarga();

        $resultado = $_SESSION['resultado'] ?? null;
        if ($resultado === null || $resultado['correcta']) {
            \Redirect::to('/usuario/lobby');
        }

        unset($_SESSION['resultado'], $_SESSION['orden_opciones']);

        $this->mostrarFinDePartida($usuarioId, $resultado['partida_id'], false, $resultado['pregunta_id']);
    }

    // Estas URLs crean la partida, muestran y marcan la pregunta: si el navegador
    // las pide por su cuenta (precarga) no se hace nada.
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
            'respuestaCorrecta' => $textoCorrecto
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
