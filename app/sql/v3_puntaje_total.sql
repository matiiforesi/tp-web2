-- ============================================================
-- juego_preguntas.sql ya la trae para las bases nuevas, pero las bases que se crearon
-- antes (la de InfinityFree, o las locales viejas) no la tienen, y el lobby, el juego
-- y el ranking dependen de ella.
--
-- ES SEGURA DE EJECUTAR AUNQUE LA COLUMNA YA EXISTA: solo la agrega si falta.
-- Ejecutar con la base ya seleccionada.
-- ============================================================

SET @existe := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuario'
      AND COLUMN_NAME = 'puntaje_total'
);

SET @sql := IF(@existe = 0,
    'ALTER TABLE usuario ADD COLUMN puntaje_total INT UNSIGNED NOT NULL DEFAULT 0 AFTER foto_perfil',
    'SELECT ''puntaje_total ya existe: no se hace nada'' AS aviso');

PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;
