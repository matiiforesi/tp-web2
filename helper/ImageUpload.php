<?php

/**
 * La foto llega como base64 dentro del formulario, no como archivo multipart:
 * InfinityFree bloquea los envios multipart/form-data con un 403.
 * El navegador ya la reduce y comprime antes de enviarla, pero aca se valida todo de nuevo:
 * se decodifica, se controla el tamano y se verifica el contenido real (no la extension).
 */
class ImageUpload
{
    // 2 MB ya decodificada
    const MAX_BYTES = 2097152;
    // Evita decodificar textos enormes
    const MAX_BASE64_CHARS = 3000000;
    const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Acepta "data:image/jpeg;base64,XXXX" o el base64 solo.
     * @return array [lista de errores, bytes de la imagen o null]
     */
    public static function decodificarBase64($dato): array
    {
        $dato = is_string($dato) ? trim($dato) : '';
        if ($dato === '') {
            return [['La foto de perfil es obligatoria.'], null];
        }

        $dato = preg_replace('#^data:[^;,]*;base64,#i', '', $dato);
        // Si algun intermediario convirtio los "+" en espacios, se restauran.
        $dato = str_replace(' ', '+', $dato);

        if (strlen($dato) > self::MAX_BASE64_CHARS) {
            return [['La foto no puede pesar más de 2 MB.'], null];
        }

        $bytes = base64_decode($dato, true);
        if ($bytes === false || $bytes === '') {
            return [['No se pudo leer la foto. Intentá de nuevo.'], null];
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            return [['La foto no puede pesar más de 2 MB.'], null];
        }

        $mime = self::detectarMime($bytes);
        if (!isset(self::TIPOS[$mime]) || @getimagesizefromstring($bytes) === false) {
            return [['La foto debe ser una imagen JPG, PNG o WEBP.'], null];
        }

        return [[], $bytes];
    }

    /** Guarda los bytes ya validados con un nombre aleatorio y devuelve ese nombre. */
    public static function guardarBytes(string $bytes, string $directorio): string
    {
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true)) {
            throw new RuntimeException('No se pudo crear el directorio de subidas.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS[self::detectarMime($bytes)];

        if (file_put_contents(rtrim($directorio, '/') . '/' . $nombre, $bytes) === false) {
            throw new RuntimeException('No se pudo guardar la foto.');
        }

        return $nombre;
    }

    public static function eliminar($nombre, $directorio): void
    {
        $ruta = rtrim($directorio, '/') . '/' . basename($nombre);
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }

    private static function detectarMime(string $bytes): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->buffer($bytes);
    }
}
