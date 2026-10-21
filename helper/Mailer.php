<?php

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/phpmailer/PHPMailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer/SMTP.php';

class Mailer
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /** Lanza RuntimeException si no se pudo enviar. */
    public function enviar(string $destinatario, string $nombre, string $asunto, string $html, string $texto): void
    {
        if (($this->config['mail_modo'] ?? 'smtp') === 'log') {
            Log::info("[MAIL simulado] Para: $destinatario | Asunto: $asunto\n$texto");
            return;
        }

        $puerto = (int)($this->config['mail_port'] ?? 587);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['mail_host'] ?? '';
            $mail->Port = $puerto;
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['mail_user'] ?? '';
            $mail->Password = $this->config['mail_pass'] ?? '';
            // 465 = SSL implicito, 587 = STARTTLS.
            $mail->SMTPSecure = $puerto === 465
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            // Si SMTP no responde, no deja colgado el registro.
            $mail->Timeout = 10;
            $mail->SMTPDebug = 0;

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom(
                $this->config['mail_from'] ?? $this->config['mail_user'] ?? '',
                $this->config['mail_from_nombre'] ?? ''
            );
            $mail->addAddress($destinatario, $nombre);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $html;
            $mail->AltBody = $texto;

            $mail->send();
        } catch (MailException $e) {
            throw new RuntimeException('Fallo el envio del mail: ' . $mail->ErrorInfo);
        }
    }
}
