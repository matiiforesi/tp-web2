-- v10: corrige la ortografia (tildes, enie y signos de apertura) de las 100 preguntas cargadas por v8.
--
-- Que hace:
--   1) Reemplaza el enunciado de cada pregunta de v8 por su version corregida.
--   2) Reemplaza el texto de las opciones de esas preguntas (solo las que tenian errores).
--
-- Es seguro ejecutarla mas de una vez: cada correccion busca el texto viejo exacto (se compara en binario,
-- por lo que distingue tildes y mayusculas) y, una vez corregido, ya no lo encuentra.
-- No toca preguntas que no sean las de v8 (las iniciales de v4 y las propuestas por usuarios quedan igual),
-- no cambia ids ni cual es la opcion correcta, y conserva el historial de partidas.
--
-- Unico cambio de estado: al corregir la ortografia, algunas preguntas de v8 quedan identicas a una pregunta
-- que ya existia (por ejemplo las de v4, que ya tenian tildes). Esas copias de v8 pasan a 'baja' para que el
-- mismo enunciado no aparezca dos veces. No se borran: el historial de partidas sigue apuntando a ellas.
-- Para volver atras con alguna: UPDATE pregunta SET estado = 'activa' WHERE id = <id>;
--
-- Requiere haber ejecutado v8. No lleva "USE": ejecutala con la base juego_preguntas ya seleccionada.
-- Importante: no vuelvas a ejecutar v8 despues de esta migration, porque v8 busca los enunciados viejos
-- y volveria a insertar las 100 preguntas sin corregir.

SET NAMES utf8mb4;

DROP TEMPORARY TABLE IF EXISTS correccion_pregunta;
CREATE TEMPORARY TABLE correccion_pregunta (
    viejo VARCHAR(500) NOT NULL,
    nuevo VARCHAR(500) NOT NULL
) DEFAULT CHARSET=utf8mb4;

INSERT INTO correccion_pregunta (viejo, nuevo) VALUES
    ('Quien fue el primer presidente de la Argentina?', '¿Quién fue el primer presidente de la Argentina?'),
    ('En que ano comenzo la Revolucion Francesa?', '¿En qué año comenzó la Revolución Francesa?'),
    ('Que civilizacion construyo Machu Picchu?', '¿Qué civilización construyó Machu Picchu?'),
    ('Quien escribio El Principe?', '¿Quién escribió El Príncipe?'),
    ('En que ano llego Cristobal Colon a America?', '¿En qué año llegó Cristóbal Colón a América?'),
    ('Que imperio construyo el Coliseo Romano?', '¿Qué imperio construyó el Coliseo Romano?'),
    ('Quien lidero la independencia de la India?', '¿Quién lideró la independencia de la India?'),
    ('En que ano cayo el Muro de Berlin?', '¿En qué año cayó el Muro de Berlín?'),
    ('Que pueblo invento la democracia en Atenas?', '¿Qué pueblo inventó la democracia en Atenas?'),
    ('Quien fue conocido como el Libertador de America del Sur?', '¿Quién fue conocido como el Libertador de América del Sur?'),
    ('Que faraon es famoso por su tumba hallada en 1922?', '¿Qué faraón es famoso por su tumba hallada en 1922?'),
    ('En que pais ocurrio la Revolucion de Octubre de 1917?', '¿En qué país ocurrió la Revolución de Octubre de 1917?'),
    ('Quien fue la primera mujer en ganar un Premio Nobel?', '¿Quién fue la primera mujer en ganar un Premio Nobel?'),
    ('Que guerra termino con el Tratado de Versalles?', '¿Qué guerra terminó con el Tratado de Versalles?'),
    ('Quien fue el primer emperador romano?', '¿Quién fue el primer emperador romano?'),
    ('Que acontecimiento se conmemora el 25 de mayo en Argentina?', '¿Qué acontecimiento se conmemora el 25 de mayo en Argentina?'),
    ('Quien lidero la campana libertadora de los Andes?', '¿Quién lideró la campaña libertadora de los Andes?'),
    ('En que siglo vivio Leonardo da Vinci?', '¿En qué siglo vivió Leonardo da Vinci?'),
    ('Que antigua ciudad fue sepultada por el Vesubio?', '¿Qué antigua ciudad fue sepultada por el Vesubio?'),
    ('Que documento limito el poder del rey ingles en 1215?', '¿Qué documento limitó el poder del rey inglés en 1215?'),
    ('Cuantos jugadores tiene un equipo de futbol en la cancha?', '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?'),
    ('En que deporte se usa un volante?', '¿En qué deporte se usa un volante?'),
    ('Cuantos anillos tiene el simbolo olimpico?', '¿Cuántos anillos tiene el símbolo olímpico?'),
    ('Que pais gano el Mundial de futbol 2022?', '¿Qué país ganó el Mundial de fútbol 2022?'),
    ('Cuanto dura un partido de futbol reglamentario?', '¿Cuánto dura un partido de fútbol reglamentario?'),
    ('En que deporte se disputa Wimbledon?', '¿En qué deporte se disputa Wimbledon?'),
    ('Cuantos puntos vale un triple en basquet?', '¿Cuántos puntos vale un triple en básquet?'),
    ('Que pieza se mueve en forma de L en ajedrez?', '¿Qué pieza se mueve en forma de L en ajedrez?'),
    ('En que deporte se utiliza una tabla y una ola?', '¿En qué deporte se utiliza una tabla y una ola?'),
    ('Cuantas bases hay en el beisbol?', '¿Cuántas bases hay en el béisbol?'),
    ('Como se llama el torneo ciclista de Francia?', '¿Cómo se llama el torneo ciclista de Francia?'),
    ('Que seleccion gano mas Copas del Mundo de futbol hasta 2022?', '¿Qué selección ganó más Copas del Mundo de fútbol hasta 2022?'),
    ('Cuantos sets debe ganar un jugador para vencer en un partido de tenis masculino de Grand Slam?', '¿Cuántos sets debe ganar un jugador para vencer en un partido de tenis masculino de Grand Slam?'),
    ('En que deporte se consigue un home run?', '¿En qué deporte se consigue un home run?'),
    ('Que color de tarjeta expulsa a un jugador en futbol?', '¿Qué color de tarjeta expulsa a un jugador en fútbol?'),
    ('Cuantos jugadores forman un equipo de voleibol en cancha?', '¿Cuántos jugadores forman un equipo de voleibol en cancha?'),
    ('Que pais es famoso por el sumo?', '¿Qué país es famoso por el sumo?'),
    ('En que deporte se usa un puck?', '¿En qué deporte se usa un puck?'),
    ('Cuantas casillas tiene un tablero de ajedrez?', '¿Cuántas casillas tiene un tablero de ajedrez?'),
    ('Que atleta gano ocho medallas de oro en Beijing 2008?', '¿Qué atleta ganó ocho medallas de oro en Beijing 2008?'),
    ('Quien escribio Don Quijote de la Mancha?', '¿Quién escribió Don Quijote de la Mancha?'),
    ('Quien pinto La Gioconda?', '¿Quién pintó La Gioconda?'),
    ('Cual es la lengua original de La Divina Comedia?', '¿Cuál es la lengua original de La Divina Comedia?'),
    ('Que instrumento tiene teclas, cuerdas y martillos?', '¿Qué instrumento tiene teclas, cuerdas y martillos?'),
    ('Quien compuso la Novena Sinfonia?', '¿Quién compuso la Novena Sinfonía?'),
    ('Que estilo arquitectonico caracteriza a Notre Dame de Paris?', '¿Qué estilo arquitectónico caracteriza a Notre Dame de París?'),
    ('Quien escribio Cien anos de soledad?', '¿Quién escribió Cien años de soledad?'),
    ('Que movimiento artistico inicio Pablo Picasso junto a Georges Braque?', '¿Qué movimiento artístico inició Pablo Picasso junto a Georges Braque?'),
    ('Como se llama el arte de doblar papel?', '¿Cómo se llama el arte de doblar papel?'),
    ('Que premio reconoce obras de literatura en lengua espanola?', '¿Qué premio reconoce obras de literatura en lengua española?'),
    ('Quien escribio Romeo y Julieta?', '¿Quién escribió Romeo y Julieta?'),
    ('Que danza es tradicional de Argentina?', '¿Qué danza es tradicional de Argentina?'),
    ('Que museo alberga la Mona Lisa?', '¿Qué museo alberga la Mona Lisa?'),
    ('Que genero literario narra hechos imaginarios?', '¿Qué género literario narra hechos imaginarios?'),
    ('Como se llama una obra teatral de humor?', '¿Cómo se llama una obra teatral de humor?'),
    ('Que poeta chileno gano el Nobel de Literatura en 1971?', '¿Qué poeta chileno ganó el Nobel de Literatura en 1971?'),
    ('Que tecnica pictorica usa pequenos puntos de color?', '¿Qué técnica pictórica usa pequeños puntos de color?'),
    ('Que edificio es un simbolo de la ciudad de Pisa?', '¿Qué edificio es un símbolo de la ciudad de Pisa?'),
    ('Que idioma tiene mas hablantes nativos?', '¿Qué idioma tiene más hablantes nativos?'),
    ('Como se llama la narracion breve con moraleja?', '¿Cómo se llama la narración breve con moraleja?'),
    ('Cual es el planeta mas grande del sistema solar?', '¿Cuál es el planeta más grande del sistema solar?'),
    ('Que gas necesitan las plantas para la fotosintesis?', '¿Qué gas necesitan las plantas para la fotosíntesis?'),
    ('Cual es el simbolo quimico del oro?', '¿Cuál es el símbolo químico del oro?'),
    ('Cuantos huesos tiene aproximadamente un adulto?', '¿Cuántos huesos tiene aproximadamente un adulto?'),
    ('Que organo bombea la sangre?', '¿Qué órgano bombea la sangre?'),
    ('Como se llama el cambio de liquido a gas?', '¿Cómo se llama el cambio de líquido a gas?'),
    ('Que fuerza atrae los cuerpos hacia la Tierra?', '¿Qué fuerza atrae los cuerpos hacia la Tierra?'),
    ('Cual es la unidad basica de la vida?', '¿Cuál es la unidad básica de la vida?'),
    ('Que planeta es conocido como el planeta rojo?', '¿Qué planeta es conocido como el planeta rojo?'),
    ('Que instrumento mide la temperatura?', '¿Qué instrumento mide la temperatura?'),
    ('Que parte de la planta absorbe agua del suelo?', '¿Qué parte de la planta absorbe agua del suelo?'),
    ('Que metal es liquido a temperatura ambiente?', '¿Qué metal es líquido a temperatura ambiente?'),
    ('Cuantos lados tiene un hexagono?', '¿Cuántos lados tiene un hexágono?'),
    ('Que particula tiene carga negativa?', '¿Qué partícula tiene carga negativa?'),
    ('Como se llama el estudio de los terremotos?', '¿Cómo se llama el estudio de los terremotos?'),
    ('Que vitamina produce el cuerpo con la luz solar?', '¿Qué vitamina produce el cuerpo con la luz solar?'),
    ('Cual es la velocidad aproximada de la luz?', '¿Cuál es la velocidad aproximada de la luz?'),
    ('Que mamifero pone huevos?', '¿Qué mamífero pone huevos?'),
    ('Que capa protege a la Tierra de gran parte de la radiacion ultravioleta?', '¿Qué capa protege a la Tierra de gran parte de la radiación ultravioleta?'),
    ('Que tipo de energia produce el Sol?', '¿Qué tipo de energía produce el Sol?'),
    ('Cual es la capital de Francia?', '¿Cuál es la capital de Francia?'),
    ('En que continente esta Egipto?', '¿En qué continente está Egipto?'),
    ('Cual es el oceano mas grande?', '¿Cuál es el océano más grande?'),
    ('Cual es el rio mas largo de Sudamerica?', '¿Cuál es el río más largo de Sudamérica?'),
    ('Que pais tiene forma de bota?', '¿Qué país tiene forma de bota?'),
    ('Cual es la montana mas alta del mundo?', '¿Cuál es la montaña más alta del mundo?'),
    ('Cual es la capital de Japon?', '¿Cuál es la capital de Japón?'),
    ('Que desierto es el mas grande y calido del mundo?', '¿Qué desierto es el más grande y cálido del mundo?'),
    ('En que pais se encuentra Machu Picchu?', '¿En qué país se encuentra Machu Picchu?'),
    ('Cual es el pais mas extenso del mundo?', '¿Cuál es el país más extenso del mundo?'),
    ('Que linea divide la Tierra en hemisferios norte y sur?', '¿Qué línea divide la Tierra en hemisferios norte y sur?'),
    ('Cual es la capital de Australia?', '¿Cuál es la capital de Australia?'),
    ('Que pais tiene como capital a El Cairo?', '¿Qué país tiene como capital a El Cairo?'),
    ('Cual es la isla mas grande del mundo?', '¿Cuál es la isla más grande del mundo?'),
    ('En que cordillera se encuentra el Aconcagua?', '¿En qué cordillera se encuentra el Aconcagua?'),
    ('Cual es la capital de Brasil?', '¿Cuál es la capital de Brasil?'),
    ('Que mar separa Europa de Africa?', '¿Qué mar separa Europa de África?'),
    ('En que pais esta la ciudad de Petra?', '¿En qué país está la ciudad de Petra?'),
    ('Cual es el lago de agua dulce mas grande por superficie?', '¿Cuál es el lago de agua dulce más grande por superficie?'),
    ('Que pais comparte la Patagonia con Argentina?', '¿Qué país comparte la Patagonia con Argentina?');

DROP TEMPORARY TABLE IF EXISTS correccion_opcion;
CREATE TEMPORARY TABLE correccion_opcion (
    viejo VARCHAR(255) NOT NULL,
    nuevo VARCHAR(255) NOT NULL
) DEFAULT CHARSET=utf8mb4;

INSERT INTO correccion_opcion (viejo, nuevo) VALUES
    ('Acropolis', 'Acrópolis'),
    ('Africa', 'África'),
    ('Akenaton', 'Akenatón'),
    ('Anemometro', 'Anemómetro'),
    ('Antonio Lopez de Santa Anna', 'Antonio López de Santa Anna'),
    ('Aristoteles', 'Aristóteles'),
    ('Artico', 'Ártico'),
    ('Astronomia', 'Astronomía'),
    ('Atlantico', 'Atlántico'),
    ('Atomo', 'Átomo'),
    ('Badminton', 'Bádminton'),
    ('Baltico', 'Báltico'),
    ('Barometro', 'Barómetro'),
    ('Batalla de Maipu', 'Batalla de Maipú'),
    ('Beisbol', 'Béisbol'),
    ('Biografia', 'Biografía'),
    ('Caligrafia', 'Caligrafía'),
    ('Canada', 'Canadá'),
    ('Celula', 'Célula'),
    ('Chino mandarin', 'Chino mandarín'),
    ('Circulo Polar Artico', 'Círculo Polar Ártico'),
    ('Codigo Napoleonico', 'Código Napoleónico'),
    ('Condensacion', 'Condensación'),
    ('Corazon', 'Corazón'),
    ('Creacion de la bandera', 'Creación de la bandera'),
    ('Cronica', 'Crónica'),
    ('Declaracion de la Independencia', 'Declaración de la Independencia'),
    ('Delfin', 'Delfín'),
    ('Dioxido de carbono', 'Dióxido de carbono'),
    ('Ecologia', 'Ecología'),
    ('Edicto de Milan', 'Edicto de Milán'),
    ('Electron', 'Electrón'),
    ('Elegia', 'Elegía'),
    ('Energia eolica', 'Energía eólica'),
    ('Energia geotermica', 'Energía geotérmica'),
    ('Energia nuclear', 'Energía nuclear'),
    ('Energia quimica', 'Energía química'),
    ('Espanol', 'Español'),
    ('Evaporacion', 'Evaporación'),
    ('Fabula', 'Fábula'),
    ('Ficcion', 'Ficción'),
    ('Frances', 'Francés'),
    ('Friccion', 'Fricción'),
    ('Fusion', 'Fusión'),
    ('Futbol', 'Fútbol'),
    ('Gabriel Garcia Marquez', 'Gabriel García Márquez'),
    ('Genetica', 'Genética'),
    ('Gotico', 'Gótico'),
    ('Guerra Fria', 'Guerra Fría'),
    ('Higado', 'Hígado'),
    ('Higrometro', 'Higrómetro'),
    ('Indico', 'Índico'),
    ('Ingles', 'Inglés'),
    ('Japon', 'Japón'),
    ('Jose de San Martin', 'José de San Martín'),
    ('Julio Cesar', 'Julio César'),
    ('Julio Cortazar', 'Julio Cortázar'),
    ('Jupiter', 'Júpiter'),
    ('Latin', 'Latín'),
    ('Martin Guemes', 'Martín Güemes'),
    ('Mediterraneo', 'Mediterráneo'),
    ('Molecula', 'Molécula'),
    ('Murcielago', 'Murciélago'),
    ('Natacion', 'Natación'),
    ('Neoclasico', 'Neoclásico'),
    ('Neron', 'Nerón'),
    ('Neutron', 'Neutrón'),
    ('Nicolas Maquiavelo', 'Nicolás Maquiavelo'),
    ('Nitrogeno', 'Nitrógeno'),
    ('Oceania', 'Oceanía'),
    ('Oxigeno', 'Oxígeno'),
    ('Pacifico', 'Pacífico'),
    ('Parana', 'Paraná'),
    ('Paris', 'París'),
    ('Paris-Roubaix', 'París-Roubaix'),
    ('Peru', 'Perú'),
    ('Platon', 'Platón'),
    ('Presion', 'Presión'),
    ('Proton', 'Protón'),
    ('Pulmon', 'Pulmón'),
    ('Raiz', 'Raíz'),
    ('Ramses II', 'Ramsés II'),
    ('Revolucion de Mayo', 'Revolución de Mayo'),
    ('Rinon', 'Riñón'),
    ('Rio de Janeiro', 'Río de Janeiro'),
    ('Romanico', 'Románico'),
    ('Sao Paulo', 'São Paulo'),
    ('Simon Bolivar', 'Simón Bolívar'),
    ('Sismologia', 'Sismología'),
    ('Solidificacion', 'Solidificación'),
    ('Termometro', 'Termómetro'),
    ('Tomas Moro', 'Tomás Moro'),
    ('Trópico de Cancer', 'Trópico de Cáncer'),
    ('Tunez', 'Túnez'),
    ('Turquia', 'Turquía'),
    ('Tutankamon', 'Tutankamón'),
    ('Violin', 'Violín'),
    ('Vuelta a Espana', 'Vuelta a España');

-- 1) Enunciados.
UPDATE pregunta p
JOIN correccion_pregunta c ON BINARY p.enunciado = BINARY c.viejo
SET p.enunciado = c.nuevo;

-- 2) Opciones, solo de las preguntas corregidas arriba.
UPDATE opcion o
JOIN pregunta p ON p.id = o.pregunta_id
JOIN correccion_pregunta c ON BINARY p.enunciado = BINARY c.nuevo
JOIN correccion_opcion m ON BINARY o.texto = BINARY m.viejo
SET o.texto = m.nuevo;

-- 3) Copias que quedaron repetidas: se da de baja la de v8 (la de mayor id) si ya hay otra activa igual.
UPDATE pregunta d
JOIN correccion_pregunta c ON BINARY d.enunciado = BINARY c.nuevo
JOIN pregunta o ON BINARY o.enunciado = BINARY d.enunciado AND o.id < d.id AND o.estado = 'activa'
SET d.estado = 'baja'
WHERE d.estado = 'activa';

DROP TEMPORARY TABLE IF EXISTS correccion_opcion;
DROP TEMPORARY TABLE IF EXISTS correccion_pregunta;

-- Verificacion (opcional): lista las preguntas cargadas por el sistema que siguen sin el signo de apertura.
-- Despues de esta migration deberia devolver 0 filas.
-- SELECT id, enunciado FROM pregunta WHERE creada_por IS NULL AND enunciado NOT LIKE '¿%';

-- Y esta lista las preguntas activas con enunciado repetido. Tambien deberia devolver 0 filas.
-- SELECT enunciado, COUNT(*) AS veces FROM pregunta WHERE estado = 'activa' GROUP BY enunciado HAVING COUNT(*) > 1;
