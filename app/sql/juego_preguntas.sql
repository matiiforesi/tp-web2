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
-- UBICACION (pais / ciudad con coordenadas para el mapa)
-- Estas tablas se borran cuando se vea la correcta implementación del mapa, también modificando Usuario.
-- ============================================================
CREATE TABLE IF NOT EXISTS pais (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pais_nombre (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ciudad (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pais_id SMALLINT UNSIGNED NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    latitud DECIMAL(9,6) NOT NULL,
    longitud DECIMAL(9,6) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ciudad_pais_nombre (pais_id, nombre),
    CONSTRAINT fk_ciudad_pais FOREIGN KEY (pais_id) REFERENCES pais (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO pais (id, nombre) VALUES
    (1, 'Argentina'), (2, 'Uruguay'), (3, 'Chile'), (4, 'Paraguay'),
    (5, 'Bolivia'), (6, 'Brasil'), (7, 'Perú'), (8, 'Colombia'),
    (9, 'México'), (10, 'España'), (11, 'Estados Unidos');

INSERT IGNORE INTO ciudad (pais_id, nombre, latitud, longitud) VALUES
    (1, 'Buenos Aires',        -34.603700, -58.381600),
    (1, 'San Justo',           -34.683300, -58.566700),
    (1, 'La Plata',            -34.920500, -57.953600),
    (1, 'Córdoba',             -31.420100, -64.188800),
    (1, 'Rosario',             -32.944200, -60.650500),
    (1, 'Mendoza',             -32.889500, -68.845800),
    (1, 'Mar del Plata',       -38.005500, -57.542600),
    (1, 'Salta',               -24.782100, -65.423200),
    (1, 'San Miguel de Tucumán', -26.808300, -65.217600),
    (2, 'Montevideo',          -34.901100, -56.164500),
    (2, 'Salto',               -31.383300, -57.966700),
    (2, 'Punta del Este',      -34.962200, -54.950700),
    (3, 'Santiago',            -33.448900, -70.669300),
    (3, 'Valparaíso',          -33.047200, -71.612700),
    (3, 'Concepción',          -36.820100, -73.044400),
    (4, 'Asunción',            -25.263700, -57.575900),
    (4, 'Ciudad del Este',     -25.509700, -54.611100),
    (5, 'La Paz',              -16.489700, -68.119300),
    (5, 'Santa Cruz de la Sierra', -17.814600, -63.156100),
    (5, 'Cochabamba',          -17.389500, -66.156800),
    (6, 'São Paulo',           -23.550500, -46.633300),
    (6, 'Río de Janeiro',      -22.906800, -43.172900),
    (6, 'Brasilia',            -15.793900, -47.882800),
    (7, 'Lima',                -12.046400, -77.042800),
    (7, 'Cusco',               -13.532000, -71.967500),
    (7, 'Arequipa',            -16.409000, -71.537500),
    (8, 'Bogotá',                4.711000, -74.072100),
    (8, 'Medellín',              6.244200, -75.581200),
    (8, 'Cali',                  3.451600, -76.532000),
    (9, 'Ciudad de México',     19.432600, -99.133200),
    (9, 'Guadalajara',          20.659700, -103.349600),
    (9, 'Monterrey',            25.686600, -100.316100),
    (10, 'Madrid',              40.416800,  -3.703800),
    (10, 'Barcelona',           41.385100,   2.173400),
    (10, 'Valencia',            39.469900,  -0.376300),
    (11, 'Nueva York',          40.712800, -74.006000),
    (11, 'Miami',               25.761700, -80.191800),
    (11, 'Los Ángeles',         34.052200, -118.243700);

-- ============================================================
-- USUARIO
-- ============================================================
CREATE TABLE IF NOT EXISTS usuario (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rol_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
    nombre_completo VARCHAR(100) NOT NULL,
    anio_nacimiento SMALLINT UNSIGNED NOT NULL,
    sexo ENUM('M','F','N') NOT NULL DEFAULT 'N' COMMENT 'M=Masculino, F=Femenino, N=Prefiero no cargarlo',
    pais_id SMALLINT UNSIGNED NOT NULL,
    ciudad_id INT UNSIGNED NOT NULL,
    email VARCHAR(100) NOT NULL,
    usuario VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) NULL,
    -- Se deja en 1 hasta implementar la validacion por mail.
    cuenta_validada TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_email (email),
    UNIQUE KEY uq_usuario_usuario (usuario),
    CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES rol (id),
    CONSTRAINT fk_usuario_pais FOREIGN KEY (pais_id) REFERENCES pais (id),
    CONSTRAINT fk_usuario_ciudad FOREIGN KEY (ciudad_id) REFERENCES ciudad (id)
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
    correcta TINYINT(1) NULL COMMENT 'NULL = todavia sin responder',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pp_partida (partida_id),
    KEY idx_pp_pregunta (pregunta_id),
    CONSTRAINT fk_pp_partida  FOREIGN KEY (partida_id) REFERENCES partida (id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_pregunta FOREIGN KEY (pregunta_id) REFERENCES pregunta (id),
    CONSTRAINT fk_pp_opcion   FOREIGN KEY (opcion_elegida_id) REFERENCES opcion (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
