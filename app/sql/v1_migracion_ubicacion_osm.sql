-- ============================================================
-- Pasa la ubicacion de los usuarios de pais_id / ciudad_id (tablas pais y ciudad)
-- a columnas propias en usuario, y elimina las tablas pais y ciudad.
--
-- Ejecutar UNA SOLA VEZ en cada base que ya tenga el modelo anterior
-- (la local de cada integrante y la de InfinityFree), desde phpMyAdmin
-- con la base ya seleccionada. Si la base se crea de cero con
-- juego_preguntas.sql, NO hace falta correr este script.
-- ============================================================

ALTER TABLE usuario
    ADD COLUMN pais_codigo CHAR(2) NULL AFTER sexo,
    ADD COLUMN pais VARCHAR(80) NULL AFTER pais_codigo,
    ADD COLUMN ciudad VARCHAR(100) NULL AFTER pais,
    ADD COLUMN latitud DECIMAL(9,6) NULL AFTER ciudad,
    ADD COLUMN longitud DECIMAL(9,6) NULL AFTER latitud;

UPDATE usuario u
    JOIN ciudad c ON c.id = u.ciudad_id
    JOIN pais p ON p.id = u.pais_id
    SET u.pais = p.nombre,
    u.ciudad = c.nombre,
    u.latitud = c.latitud,
    u.longitud = c.longitud,
    u.pais_codigo = CASE p.nombre
        WHEN 'Argentina' THEN 'AR'
        WHEN 'Uruguay' THEN 'UY'
        WHEN 'Chile' THEN 'CL'
        WHEN 'Paraguay' THEN 'PY'
        WHEN 'Bolivia' THEN 'BO'
        WHEN 'Brasil' THEN 'BR'
        WHEN 'Perú' THEN 'PE'
        WHEN 'Colombia' THEN 'CO'
        WHEN 'México' THEN 'MX'
        WHEN 'España' THEN 'ES'
        WHEN 'Estados Unidos' THEN 'US'
    END;

ALTER TABLE usuario
    MODIFY pais_codigo CHAR(2) NOT NULL,
    MODIFY pais VARCHAR(80) NOT NULL,
    MODIFY ciudad VARCHAR(100) NOT NULL,
    MODIFY latitud DECIMAL(9,6) NOT NULL,
    MODIFY longitud DECIMAL(9,6) NOT NULL;

ALTER TABLE usuario
    DROP FOREIGN KEY fk_usuario_pais,
    DROP FOREIGN KEY fk_usuario_ciudad;

ALTER TABLE usuario
    DROP COLUMN pais_id,
    DROP COLUMN ciudad_id,
    ADD KEY idx_usuario_pais (pais_codigo);

DROP TABLE ciudad;
DROP TABLE pais;
