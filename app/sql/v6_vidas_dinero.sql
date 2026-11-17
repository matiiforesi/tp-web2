-- Ejecutar una sola vez sobre una base existente que todavía no tenga estas columnas.
-- Las bases nuevas ya las crean en juego_preguntas.sql.
USE juego_preguntas;

ALTER TABLE usuario ADD COLUMN dinero INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE usuario ADD COLUMN vidas TINYINT UNSIGNED NOT NULL DEFAULT 5;
ALTER TABLE usuario ADD COLUMN vidas_actualizadas_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE usuario ADD COLUMN premio_ranking_fecha DATE NULL;
