CREATE DATABASE IF NOT EXISTS juego_preguntas CHARACTER SET utf8mb4;
USE juego_preguntas;

-- ============================================================
-- ROLES
-- ============================================================
CREATE TABLE IF NOT EXISTS rol (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rol_nombre (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO rol (id, nombre) VALUES
    (1, 'jugador'),
    (2, 'editor'),
    (3, 'administrador');

-- ============================================================
-- USUARIO
-- ============================================================
CREATE TABLE IF NOT EXISTS usuario (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rol_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
    nombre_completo VARCHAR(100) NOT NULL,
    anio_nacimiento SMALLINT UNSIGNED NOT NULL,
    sexo ENUM('M','F','N') NOT NULL DEFAULT 'N' COMMENT 'M=Masculino, F=Femenino, N=Prefiero no cargarlo',
    pais_codigo CHAR(2) NOT NULL,
    pais VARCHAR(80) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    latitud DECIMAL(9,6) NOT NULL,
    longitud DECIMAL(9,6) NOT NULL,
    email VARCHAR(100) NOT NULL,
    usuario VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) NULL,
    -- 0 hasta que el usuario active la cuenta.
    cuenta_validada TINYINT(1) NOT NULL DEFAULT 0,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_email (email),
    UNIQUE KEY uq_usuario_usuario (usuario),
    KEY idx_usuario_pais (pais_codigo),
    CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES rol (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- VERIFICACION DE CUENTA
-- Guarda solo el hash del token. El token real viaja unicamente en el link del mail.
-- ============================================================
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

-- ============================================================
-- CATEGORIAS, PREGUNTAS Y OPCIONES
-- ============================================================
CREATE TABLE IF NOT EXISTS categoria (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(40) NOT NULL,
    color CHAR(7) NOT NULL COMMENT 'Hexadecimal, ej: #E4572E',
    PRIMARY KEY (id),
    UNIQUE KEY uq_categoria_nombre (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categoria (id, nombre, color) VALUES
    (1, 'Historia','#B8860B'),
    (2, 'Deportes','#2E8B57'),
    (3, 'Cultura','#8E44AD'),
    (4, 'Ciencia','#2980B9'),
    (5, 'Geografía','#E67E22'),
    (6, 'Entretenimiento','#D63384');

CREATE TABLE IF NOT EXISTS pregunta (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id TINYINT UNSIGNED NOT NULL,
    enunciado VARCHAR(500) NOT NULL,
    estado ENUM('activa','pendiente','reportada','baja') NOT NULL DEFAULT 'pendiente',
    creada_por INT UNSIGNED NULL COMMENT 'NULL si la cargo un editor o el sistema',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pregunta_estado (estado),
    CONSTRAINT fk_pregunta_categoria FOREIGN KEY (categoria_id) REFERENCES categoria (id),
    CONSTRAINT fk_pregunta_creada_por FOREIGN KEY (creada_por) REFERENCES usuario (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS opcion (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta_id INT UNSIGNED NOT NULL,
    texto VARCHAR(255) NOT NULL,
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_opcion_pregunta (pregunta_id),
    CONSTRAINT fk_opcion_pregunta FOREIGN KEY (pregunta_id) REFERENCES pregunta (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reporte_pregunta (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    motivo VARCHAR(255) NULL,
    resuelto TINYINT(1) NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_reporte_pregunta FOREIGN KEY (pregunta_id) REFERENCES pregunta (id) ON DELETE CASCADE,
    CONSTRAINT fk_reporte_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PARTIDAS
-- ============================================================
CREATE TABLE IF NOT EXISTS partida (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    puntaje INT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('en_curso','finalizada') NOT NULL DEFAULT 'en_curso',
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_partida_usuario (usuario_id),
    CONSTRAINT fk_partida_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partida_pregunta (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    partida_id INT UNSIGNED NOT NULL,
    pregunta_id INT UNSIGNED NOT NULL,
    opcion_elegida_id INT UNSIGNED NULL,
    correcta TINYINT(1) NULL COMMENT 'NULL = sin responder',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pp_partida (partida_id),
    KEY idx_pp_pregunta (pregunta_id),
    CONSTRAINT fk_pp_partida  FOREIGN KEY (partida_id) REFERENCES partida (id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_pregunta FOREIGN KEY (pregunta_id) REFERENCES pregunta (id),
    CONSTRAINT fk_pp_opcion   FOREIGN KEY (opcion_elegida_id) REFERENCES opcion (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
