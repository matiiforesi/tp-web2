-- Ejecutar una sola vez sobre una base existente que no tenga estas columnas.
USE juego_preguntas;

ALTER TABLE usuario ADD COLUMN puntaje_diario INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE usuario ADD COLUMN puntaje_diario_fecha DATE NULL;
