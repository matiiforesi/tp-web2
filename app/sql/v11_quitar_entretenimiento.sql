-- Quita la categoria Entretenimiento (tenia solo 4 preguntas) junto con sus preguntas.
-- Ejecutar una vez por base de datos (local e InfinityFree). Es idempotente: si ya no existe, no hace nada.
-- Sin USE ni COLLATE, para poder pegarlo tal cual en phpMyAdmin de InfinityFree.
--
-- ATENCION: se borran tambien las respuestas historicas de esas preguntas (partida_pregunta), porque la
-- clave foranea no permite dejar respuestas sobre una pregunta inexistente. El puntaje acumulado de los
-- usuarios (usuario.puntaje_total) y el de cada partida (partida.puntaje) no se modifican.

-- 1) Respuestas dadas a preguntas de la categoria.
DELETE FROM partida_pregunta
WHERE pregunta_id IN (
    SELECT id FROM pregunta
    WHERE categoria_id IN (SELECT id FROM categoria WHERE nombre = 'Entretenimiento')
);

-- 2) Las preguntas (sus opciones y reportes se borran solas por ON DELETE CASCADE).
DELETE FROM pregunta
WHERE categoria_id IN (SELECT id FROM categoria WHERE nombre = 'Entretenimiento');

-- 3) La categoria.
DELETE FROM categoria WHERE nombre = 'Entretenimiento';
