<?php

class Request
{
    public static function get($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function post($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * false para los pedidos que el navegador hace solo (imagenes, scripts, precargas) y que no
     * son una navegacion del usuario. Los navegadores modernos lo informan con los encabezados
     * Sec-Fetch-* (solo en HTTPS o localhost); si no vienen, se asume que si es una navegacion.
     */
    public static function esNavegacion(): bool
    {
        $destino = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? 'document';
        $proposito = $_SERVER['HTTP_SEC_PURPOSE'] ?? '';

        return $destino === 'document'
            && stripos($proposito, 'prefetch') === false
            && stripos($proposito, 'prerender') === false;
    }
}
