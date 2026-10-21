<?php

namespace app\service;

use app\model\VerificacionCuentaModel;
use Log;
use Mailer;
use RuntimeException;
use Throwable;

/** Genera el token, arma el link y manda el mail. */
class VerificacionCuentaService
{
    private $verificaciones;
    private $mailer;
    private $appUrl;

    /** $appUrl: URL base fija del sitio en config.ini. */
    public function __construct(VerificacionCuentaModel $verificaciones, Mailer $mailer, string $appUrl)
    {
        $this->verificaciones = $verificaciones;
        $this->mailer = $mailer;
        $this->appUrl = $appUrl;
    }

    /**
     * Crea un token nuevo y manda el mail de activacion.
     * Si algo falla lo guarda registra en el log y devuelve false para que el usuario puede usar "reenviar".
     */
    public function enviarMail(int $usuarioId, string $email, string $nombre): bool
    {
        try {
            if ($this->appUrl === '') {
                throw new RuntimeException('Falta app_url en config.ini.');
            }

            $token = $this->verificaciones->crearToken($usuarioId);
            $link = $this->appUrl . '/usuario/validar?token=' . $token;

            $nombreHtml = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
            $linkHtml = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $horas = VerificacionCuentaModel::HORAS_VIGENCIA;

            $html = "<p>Hola, $nombreHtml:</p>"
                . "<p>Para activar tu cuenta en Juego de Preguntas, entrá al siguiente link:</p>"
                . "<p><a href=\"$linkHtml\">Activar mi cuenta</a></p>"
                . "<p>El link vence en $horas horas. Si no creaste esta cuenta, ignorá este mail.</p>";

            $texto = "Hola, $nombre:\n\n"
                . "Para activar tu cuenta en Juego de Preguntas, entrá al siguiente link:\n$link\n\n"
                . "El link vence en $horas horas. Si no creaste esta cuenta, ignorá este mail.";

            $this->mailer->enviar($email, $nombre, 'Activá tu cuenta - Juego de Preguntas', $html, $texto);
            return true;
        } catch (Throwable $e) {
            Log::error("No se pudo enviar el mail de activacion (usuario $usuarioId): " . $e->getMessage());

            try {
                $this->verificaciones->eliminarPorUsuario($usuarioId);
            } catch (Throwable $ignorado) {
            }
            return false;
        }
    }

    public function reenviar(int $usuarioId, string $email, string $nombre): bool
    {
        try {
            if (!$this->verificaciones->puedeReenviar($usuarioId)) {
                return false;
            }
        } catch (Throwable $e) {
            Log::error("No se pudo consultar la espera de reenvio (usuario $usuarioId): " . $e->getMessage());
            return false;
        }

        return $this->enviarMail($usuarioId, $email, $nombre);
    }

    public function validar(string $token): string
    {
        return $this->verificaciones->validar($token);
    }
}
