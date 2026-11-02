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

-- ============================================================
-- PREGUNTAS
-- ============================================================

-- Categoría 1: Historia
    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (1,
            '¿Quién fue el primer presidente de la Nación Argentina?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Bernardino Rivadavia', 1),
           (@id_p, 'Julio Argentino Roca', 0),
           (@id_p, 'Domingo Faustino Sarmiento', 0),
           (@id_p, 'Bartolomé Mitre', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (1,
            '¿En qué año llegó el hombre a la Luna?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, '1969', 1),
           (@id_p, '1959', 0),
           (@id_p, '1972', 0),
           (@id_p, '1965', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (1,
            '¿Quién pintó la Mona Lisa?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Pablo Picasso', 0),
           (@id_p, 'Vincent van Gogh', 0),
           (@id_p, 'Leonardo da Vinci', 1),
           (@id_p, 'Miguel Ángel', 0);

-- Categoría 2: Deportes
    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (2,
            '¿Qué país ganó el Mundial de Fútbol de 2022?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Francia', 0),
           (@id_p, 'Argentina', 1),
           (@id_p, 'Brasil', 0),
           (@id_p, 'Alemania', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (2,
            '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, '9', 0),
           (@id_p, '10', 0),
           (@id_p, '11', 1),
           (@id_p, '12', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (2,
            '¿En qué deporte se utiliza una raqueta y un volante?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Tenis', 0),
           (@id_p, 'Pádel', 0),
           (@id_p, 'Bádminton', 1),
           (@id_p, 'Squash', 0);

-- Categoría 3: Cultura
    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (3,
            '¿Quién escribió "El Quijote"?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Gabriel García Márquez', 0),
           (@id_p, 'Miguel de Cervantes', 1),
           (@id_p, 'Jorge Luis Borges', 0),
           (@id_p, 'Pablo Neruda', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (3,
            '¿Cuál es el idioma más hablado del mundo como lengua materna?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Inglés', 0),
           (@id_p, 'Español', 0),
           (@id_p, 'Mandarín', 1),
           (@id_p, 'Hindi', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (3,
            '¿Quién es el autor de la famosa obra "La noche estrellada"?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Claude Monet', 0),
           (@id_p, 'Pablo Picasso', 0),
           (@id_p, 'Vincent van Gogh', 1),
           (@id_p, 'Salvador Dalí', 0);

-- Categoría 4: Ciencia
    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (4,
            '¿Cuál es el planeta más grande del sistema solar?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Marte', 0),
           (@id_p, 'Júpiter', 1),
           (@id_p, 'Tierra', 0),
           (@id_p, 'Saturno', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (4,
            '¿Cuál es el símbolo químico del oro?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Ag', 0),
           (@id_p, 'Au', 1),
           (@id_p, 'Fe', 0),
           (@id_p, 'Cu', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (4,
            '¿Qué gas respiran las plantas para realizar la fotosíntesis?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Oxígeno', 0),
           (@id_p, 'Nitrógeno', 0),
           (@id_p, 'Dióxido de carbono', 1),
           (@id_p, 'Hidrógeno', 0);

-- Categoría 5: Geografía
    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (5,
            '¿Cuál es el río más largo del mundo?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Nilo', 1),
           (@id_p, 'Amazonas', 0),
           (@id_p, 'Misisipi', 0),
           (@id_p, 'Yangtsé', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (5,
            '¿Cuál es la capital de Francia?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Londres', 0),
           (@id_p, 'París', 1),
           (@id_p, 'Berlín', 0),
           (@id_p, 'Madrid', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (5,
            '¿En qué continente se encuentra Egipto?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Asia', 0),
           (@id_p, 'Europa', 0),
           (@id_p, 'África', 1),
           (@id_p, 'Oceanía', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (5,
            '¿Cuál es el océano más grande del mundo?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Atlántico', 0),
           (@id_p, 'Índico', 0),
           (@id_p, 'Pacífico', 1),
           (@id_p, 'Ártico', 0);

-- Categoría 6: Entretenimiento
    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (6,
            '¿Quién es el protagonista de la saga "Piratas del Caribe"?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Will Smith', 0),
           (@id_p, 'Johnny Depp', 1),
           (@id_p, 'Brad Pitt', 0),
           (@id_p, 'Tom Cruise', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (6,
            '¿En qué ciudad ficticia vive Batman?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Metrópolis', 0),
           (@id_p, 'Gotham City', 1),
           (@id_p, 'Ciudad Estrella', 0),
           (@id_p, 'Central City', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (6,
            '¿Qué banda británica compuso la canción "Hey Jude"?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'The Rolling Stones', 0),
           (@id_p, 'Queen', 0),
           (@id_p, 'The Beatles', 1),
           (@id_p, 'Pink Floyd', 0);

    INSERT INTO pregunta (categoria_id, enunciado, estado)
    VALUES (6,
            '¿Cuál es el nombre del ratón más famoso de Disney?',
            'activa');
    SET @id_p = LAST_INSERT_ID();
    INSERT INTO opcion (pregunta_id, texto, es_correcta)
    VALUES (@id_p, 'Jerry', 0),
           (@id_p, 'Mickey Mouse', 1),
           (@id_p, 'Speedy Gonzales', 0),
           (@id_p, 'Topo Gigio', 0);