-- ============================================================
-- Crea la tabla verificacion_cuenta (guarda el HASH del token, nunca el token).
-- Cambia el default de usuario.cuenta_validada de 1 a 0, para que las cuentas
-- nuevas nazcan sin validar. Los usuarios que ya existen quedan activos.
--
-- Ejecutar UNA SOLA VEZ en cada base que ya tenga el modelo anterior
-- (la local de cada integrante y la de InfinityFree), desde phpMyAdmin
-- con la base ya seleccionada. Si la base se crea de cero
-- con juego_preguntas.sql, NO hace falta correr este script.
-- ============================================================

UPDATE usuario SET cuenta_validada = 1;

ALTER TABLE usuario
    MODIFY cuenta_validada TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS verificacion_cuenta (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL COMMENT 'Hex del token enviado por mail',
    expira DATETIME NOT NULL,
    enviado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_verificacion_token (token_hash),
    KEY idx_verificacion_usuario (usuario_id),
    CONSTRAINT fk_verificacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
