-- ============================================================
-- Antes estas preguntas estaban cargadas dentro de juego_preguntas.sql.
-- Se pasan a una migracion para que el esquema base quede sin datos de prueba.
--
-- ES SEGURA DE EJECUTAR EN UNA BASE QUE YA TIENE LAS PREGUNTAS CARGADAS PORQUE:
--   * una pregunta solo se inserta si no existe otra con el mismo enunciado;
--   * las opciones solo se insertan si la pregunta todavia no tiene ninguna
--     (asi no se pisan ni se duplican opciones editadas a mano).
--
-- Requiere que existan las tablas categoria, pregunta y opcion
-- (creadas por juego_preguntas.sql). Ejecutar con la base ya seleccionada.
-- Se puede correr varias veces sin duplicar nada.
-- ============================================================

SET NAMES utf8mb4;

-- Categoría 1: Historia

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Quién fue el primer presidente de la Nación Argentina?', 'activa'
FROM categoria c
WHERE c.nombre = 'Historia'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Quién fue el primer presidente de la Nación Argentina?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Bernardino Rivadavia' AS texto, 1 AS es_correcta
        UNION ALL SELECT 'Julio Argentino Roca', 0
        UNION ALL SELECT 'Domingo Faustino Sarmiento', 0
        UNION ALL SELECT 'Bartolomé Mitre', 0
     ) o
WHERE p.enunciado = '¿Quién fue el primer presidente de la Nación Argentina?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿En qué año llegó el hombre a la Luna?', 'activa'
FROM categoria c
WHERE c.nombre = 'Historia'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿En qué año llegó el hombre a la Luna?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT '1969' AS texto, 1 AS es_correcta
        UNION ALL SELECT '1959', 0
        UNION ALL SELECT '1972', 0
        UNION ALL SELECT '1965', 0
     ) o
WHERE p.enunciado = '¿En qué año llegó el hombre a la Luna?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Quién pintó la Mona Lisa?', 'activa'
FROM categoria c
WHERE c.nombre = 'Historia'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Quién pintó la Mona Lisa?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Pablo Picasso' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Vincent van Gogh', 0
        UNION ALL SELECT 'Leonardo da Vinci', 1
        UNION ALL SELECT 'Miguel Ángel', 0
     ) o
WHERE p.enunciado = '¿Quién pintó la Mona Lisa?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

-- Categoría 2: Deportes

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Qué país ganó el Mundial de Fútbol de 2022?', 'activa'
FROM categoria c
WHERE c.nombre = 'Deportes'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Qué país ganó el Mundial de Fútbol de 2022?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Francia' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Argentina', 1
        UNION ALL SELECT 'Brasil', 0
        UNION ALL SELECT 'Alemania', 0
     ) o
WHERE p.enunciado = '¿Qué país ganó el Mundial de Fútbol de 2022?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?', 'activa'
FROM categoria c
WHERE c.nombre = 'Deportes'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT '9' AS texto, 0 AS es_correcta
        UNION ALL SELECT '10', 0
        UNION ALL SELECT '11', 1
        UNION ALL SELECT '12', 0
     ) o
WHERE p.enunciado = '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿En qué deporte se utiliza una raqueta y un volante?', 'activa'
FROM categoria c
WHERE c.nombre = 'Deportes'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿En qué deporte se utiliza una raqueta y un volante?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Tenis' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Pádel', 0
        UNION ALL SELECT 'Bádminton', 1
        UNION ALL SELECT 'Squash', 0
     ) o
WHERE p.enunciado = '¿En qué deporte se utiliza una raqueta y un volante?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

-- Categoría 3: Cultura

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Quién escribió "El Quijote"?', 'activa'
FROM categoria c
WHERE c.nombre = 'Cultura'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Quién escribió "El Quijote"?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Gabriel García Márquez' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Miguel de Cervantes', 1
        UNION ALL SELECT 'Jorge Luis Borges', 0
        UNION ALL SELECT 'Pablo Neruda', 0
     ) o
WHERE p.enunciado = '¿Quién escribió "El Quijote"?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuál es el idioma más hablado del mundo como lengua materna?', 'activa'
FROM categoria c
WHERE c.nombre = 'Cultura'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuál es el idioma más hablado del mundo como lengua materna?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Inglés' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Español', 0
        UNION ALL SELECT 'Mandarín', 1
        UNION ALL SELECT 'Hindi', 0
     ) o
WHERE p.enunciado = '¿Cuál es el idioma más hablado del mundo como lengua materna?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Quién es el autor de la famosa obra "La noche estrellada"?', 'activa'
FROM categoria c
WHERE c.nombre = 'Cultura'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Quién es el autor de la famosa obra "La noche estrellada"?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Claude Monet' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Pablo Picasso', 0
        UNION ALL SELECT 'Vincent van Gogh', 1
        UNION ALL SELECT 'Salvador Dalí', 0
     ) o
WHERE p.enunciado = '¿Quién es el autor de la famosa obra "La noche estrellada"?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

-- Categoría 4: Ciencia

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuál es el planeta más grande del sistema solar?', 'activa'
FROM categoria c
WHERE c.nombre = 'Ciencia'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuál es el planeta más grande del sistema solar?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Marte' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Júpiter', 1
        UNION ALL SELECT 'Tierra', 0
        UNION ALL SELECT 'Saturno', 0
     ) o
WHERE p.enunciado = '¿Cuál es el planeta más grande del sistema solar?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuál es el símbolo químico del oro?', 'activa'
FROM categoria c
WHERE c.nombre = 'Ciencia'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuál es el símbolo químico del oro?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Ag' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Au', 1
        UNION ALL SELECT 'Fe', 0
        UNION ALL SELECT 'Cu', 0
     ) o
WHERE p.enunciado = '¿Cuál es el símbolo químico del oro?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Qué gas respiran las plantas para realizar la fotosíntesis?', 'activa'
FROM categoria c
WHERE c.nombre = 'Ciencia'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Qué gas respiran las plantas para realizar la fotosíntesis?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Oxígeno' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Nitrógeno', 0
        UNION ALL SELECT 'Dióxido de carbono', 1
        UNION ALL SELECT 'Hidrógeno', 0
     ) o
WHERE p.enunciado = '¿Qué gas respiran las plantas para realizar la fotosíntesis?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

-- Categoría 5: Geografía

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuál es el río más largo del mundo?', 'activa'
FROM categoria c
WHERE c.nombre = 'Geografía'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuál es el río más largo del mundo?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Nilo' AS texto, 1 AS es_correcta
        UNION ALL SELECT 'Amazonas', 0
        UNION ALL SELECT 'Misisipi', 0
        UNION ALL SELECT 'Yangtsé', 0
     ) o
WHERE p.enunciado = '¿Cuál es el río más largo del mundo?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuál es la capital de Francia?', 'activa'
FROM categoria c
WHERE c.nombre = 'Geografía'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuál es la capital de Francia?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Londres' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'París', 1
        UNION ALL SELECT 'Berlín', 0
        UNION ALL SELECT 'Madrid', 0
     ) o
WHERE p.enunciado = '¿Cuál es la capital de Francia?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿En qué continente se encuentra Egipto?', 'activa'
FROM categoria c
WHERE c.nombre = 'Geografía'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿En qué continente se encuentra Egipto?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Asia' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Europa', 0
        UNION ALL SELECT 'África', 1
        UNION ALL SELECT 'Oceanía', 0
     ) o
WHERE p.enunciado = '¿En qué continente se encuentra Egipto?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuál es el océano más grande del mundo?', 'activa'
FROM categoria c
WHERE c.nombre = 'Geografía'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuál es el océano más grande del mundo?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Atlántico' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Índico', 0
        UNION ALL SELECT 'Pacífico', 1
        UNION ALL SELECT 'Ártico', 0
     ) o
WHERE p.enunciado = '¿Cuál es el océano más grande del mundo?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

-- Categoría 6: Entretenimiento

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Quién es el protagonista de la saga "Piratas del Caribe"?', 'activa'
FROM categoria c
WHERE c.nombre = 'Entretenimiento'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Quién es el protagonista de la saga "Piratas del Caribe"?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Will Smith' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Johnny Depp', 1
        UNION ALL SELECT 'Brad Pitt', 0
        UNION ALL SELECT 'Tom Cruise', 0
     ) o
WHERE p.enunciado = '¿Quién es el protagonista de la saga "Piratas del Caribe"?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿En qué ciudad ficticia vive Batman?', 'activa'
FROM categoria c
WHERE c.nombre = 'Entretenimiento'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿En qué ciudad ficticia vive Batman?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Metrópolis' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Gotham City', 1
        UNION ALL SELECT 'Ciudad Estrella', 0
        UNION ALL SELECT 'Central City', 0
     ) o
WHERE p.enunciado = '¿En qué ciudad ficticia vive Batman?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Qué banda británica compuso la canción "Hey Jude"?', 'activa'
FROM categoria c
WHERE c.nombre = 'Entretenimiento'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Qué banda británica compuso la canción "Hey Jude"?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'The Rolling Stones' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Queen', 0
        UNION ALL SELECT 'The Beatles', 1
        UNION ALL SELECT 'Pink Floyd', 0
     ) o
WHERE p.enunciado = '¿Qué banda británica compuso la canción "Hey Jude"?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, '¿Cuál es el nombre del ratón más famoso de Disney?', 'activa'
FROM categoria c
WHERE c.nombre = 'Entretenimiento'
  AND NOT EXISTS (SELECT 1 FROM pregunta x WHERE x.enunciado = '¿Cuál es el nombre del ratón más famoso de Disney?');

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, o.texto, o.es_correcta
FROM pregunta p
CROSS JOIN (
        SELECT 'Jerry' AS texto, 0 AS es_correcta
        UNION ALL SELECT 'Mickey Mouse', 1
        UNION ALL SELECT 'Speedy Gonzales', 0
        UNION ALL SELECT 'Topo Gigio', 0
     ) o
WHERE p.enunciado = '¿Cuál es el nombre del ratón más famoso de Disney?'
  AND NOT EXISTS (SELECT 1 FROM opcion y WHERE y.pregunta_id = p.id);
