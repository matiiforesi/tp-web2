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

        // La pagina con la pregunta no se guarda en cache. Esto hace que
        // el servidor detecte como abandono el volver atras o recargar.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $this->render->renderiza('partida', [
            'pregunta' => $pregunta,
            'puntajeTotal' => $this->model->getPuntajeTotal($usuarioId),
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
        $opcion = ($opcionId === false) ? null : $this->model->getOpcion($opcionId);

        // Una respuesta repetida, vieja o que no corresponde a la pregunta mostrada se ignora.
        if ($pendiente === null || $opcion === null
            || (int)$opcion['pregunta_id'] !== (int)$pendiente['pregunta_id']) {
            http_response_code(204);
            return;
        }

        $correcta = (int)$opcion['es_correcta'] === 1;
        $registrada = $this->partidas->registrarRespuesta(
            $partidaId, $usuarioId, (int)$pendiente['pregunta_id'], $opcionId, $correcta
        );

        if (!$registrada) {
            http_response_code(204);
            return;
        }

        if ($correcta) {
            \Redirect::to('/partida/jugar');
        }

        unset($_SESSION['partida_id']);
        \Redirect::to('/usuario/lobby');
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

    private function exigirLogin(): int
    {
        if (!isset($_SESSION['usuario_id'])) {
            \Redirect::to('/usuario/login');
        }
        return (int)$_SESSION['usuario_id'];
    }
}
