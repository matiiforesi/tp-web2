<?php

class ImageUpload
{
    const MAX_BYTES = 2097152; // 2 MB
    const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** @return string[] lista de errores (vacia si el archivo es valido) */
    public static function validar($archivo, $obligatorio = true): array
    {
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $obligatorio ? ['La foto de perfil es obligatoria.'] : [];
        }

        if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
            return ['La foto no puede pesar más de 2 MB.'];
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            return ['No se pudo subir la foto. Intentá de nuevo.'];
        }
        if ($archivo['size'] > self::MAX_BYTES) {
            return ['La foto no puede pesar más de 2 MB.'];
        }

        $mime = self::detectarMime($archivo['tmp_name']);
        if (!isset(self::TIPOS[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
            return ['La foto debe ser una imagen JPG, PNG o WEBP.'];
        }

        return [];
    }

    /** Guarda la imagen ya validada con un nombre aleatorio y devuelve ese nombre. */
    public static function guardar($archivo, $directorio): string
    {
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true)) {
            throw new RuntimeException('No se pudo crear el directorio de subidas.');
        }

        $extension = self::TIPOS[self::detectarMime($archivo['tmp_name'])];
        $nombre = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($archivo['tmp_name'], rtrim($directorio, '/') . '/' . $nombre)) {
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

    private static function detectarMime($ruta): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string)$finfo->file($ruta);
    }
}
