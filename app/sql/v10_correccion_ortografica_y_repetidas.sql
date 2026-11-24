-- v10: corrige la ortografia (tildes, enie y signos de apertura) de las 100 preguntas cargadas por v8.
--
-- Que hace:
--   1) Reemplaza el enunciado de cada pregunta de v8 por su version corregida.
--   2) Reemplaza el texto de las opciones de esas preguntas (solo las que tenian errores).
--   3) Da de baja las copias de v8 que, ya corregidas, quedan identicas a otra pregunta activa.
--
-- Es seguro ejecutarla mas de una vez: cada correccion busca el texto viejo exacto (se compara en binario,
-- por lo que distingue tildes y mayusculas) y, una vez corregido, ya no lo encuentra.
-- No toca preguntas que no sean las de v8 (las iniciales de v4 y las propuestas por usuarios quedan igual),
-- no cambia ids ni cual es la opcion correcta, y conserva el historial de partidas.
--
-- Paso 3: al corregir la ortografia, algunas preguntas de v8 quedan identicas a una pregunta que ya existia
-- (por ejemplo las de v4, que ya tenian tildes). Esas copias de v8 pasan a 'baja' para que el mismo
-- enunciado no aparezca dos veces. No se borran: el historial de partidas sigue apuntando a ellas.
-- Para volver atras con alguna: UPDATE pregunta SET estado = 'activa' WHERE id = <id>;
--
-- No usa tablas temporales ni joins grandes (los hostings compartidos limitan ambas cosas).
-- No lleva "USE": ejecutala con la base juego_preguntas ya seleccionada.
-- Importante: no vuelvas a ejecutar v8 despues de esta migration, porque v8 busca los enunciados viejos
-- y volveria a insertar las 100 preguntas sin corregir.

SET NAMES utf8mb4;

-- 1) Enunciados.
UPDATE pregunta
SET enunciado = CASE BINARY enunciado
    WHEN 'Quien fue el primer presidente de la Argentina?' THEN '¿Quién fue el primer presidente de la Argentina?'
    WHEN 'En que ano comenzo la Revolucion Francesa?' THEN '¿En qué año comenzó la Revolución Francesa?'
    WHEN 'Que civilizacion construyo Machu Picchu?' THEN '¿Qué civilización construyó Machu Picchu?'
    WHEN 'Quien escribio El Principe?' THEN '¿Quién escribió El Príncipe?'
    WHEN 'En que ano llego Cristobal Colon a America?' THEN '¿En qué año llegó Cristóbal Colón a América?'
    WHEN 'Que imperio construyo el Coliseo Romano?' THEN '¿Qué imperio construyó el Coliseo Romano?'
    WHEN 'Quien lidero la independencia de la India?' THEN '¿Quién lideró la independencia de la India?'
    WHEN 'En que ano cayo el Muro de Berlin?' THEN '¿En qué año cayó el Muro de Berlín?'
    WHEN 'Que pueblo invento la democracia en Atenas?' THEN '¿Qué pueblo inventó la democracia en Atenas?'
    WHEN 'Quien fue conocido como el Libertador de America del Sur?' THEN '¿Quién fue conocido como el Libertador de América del Sur?'
    WHEN 'Que faraon es famoso por su tumba hallada en 1922?' THEN '¿Qué faraón es famoso por su tumba hallada en 1922?'
    WHEN 'En que pais ocurrio la Revolucion de Octubre de 1917?' THEN '¿En qué país ocurrió la Revolución de Octubre de 1917?'
    WHEN 'Quien fue la primera mujer en ganar un Premio Nobel?' THEN '¿Quién fue la primera mujer en ganar un Premio Nobel?'
    WHEN 'Que guerra termino con el Tratado de Versalles?' THEN '¿Qué guerra terminó con el Tratado de Versalles?'
    WHEN 'Quien fue el primer emperador romano?' THEN '¿Quién fue el primer emperador romano?'
    WHEN 'Que acontecimiento se conmemora el 25 de mayo en Argentina?' THEN '¿Qué acontecimiento se conmemora el 25 de mayo en Argentina?'
    WHEN 'Quien lidero la campana libertadora de los Andes?' THEN '¿Quién lideró la campaña libertadora de los Andes?'
    WHEN 'En que siglo vivio Leonardo da Vinci?' THEN '¿En qué siglo vivió Leonardo da Vinci?'
    WHEN 'Que antigua ciudad fue sepultada por el Vesubio?' THEN '¿Qué antigua ciudad fue sepultada por el Vesubio?'
    WHEN 'Que documento limito el poder del rey ingles en 1215?' THEN '¿Qué documento limitó el poder del rey inglés en 1215?'
    WHEN 'Cuantos jugadores tiene un equipo de futbol en la cancha?' THEN '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?'
    WHEN 'En que deporte se usa un volante?' THEN '¿En qué deporte se usa un volante?'
    WHEN 'Cuantos anillos tiene el simbolo olimpico?' THEN '¿Cuántos anillos tiene el símbolo olímpico?'
    WHEN 'Que pais gano el Mundial de futbol 2022?' THEN '¿Qué país ganó el Mundial de fútbol 2022?'
    WHEN 'Cuanto dura un partido de futbol reglamentario?' THEN '¿Cuánto dura un partido de fútbol reglamentario?'
    WHEN 'En que deporte se disputa Wimbledon?' THEN '¿En qué deporte se disputa Wimbledon?'
    WHEN 'Cuantos puntos vale un triple en basquet?' THEN '¿Cuántos puntos vale un triple en básquet?'
    WHEN 'Que pieza se mueve en forma de L en ajedrez?' THEN '¿Qué pieza se mueve en forma de L en ajedrez?'
    WHEN 'En que deporte se utiliza una tabla y una ola?' THEN '¿En qué deporte se utiliza una tabla y una ola?'
    WHEN 'Cuantas bases hay en el beisbol?' THEN '¿Cuántas bases hay en el béisbol?'
    WHEN 'Como se llama el torneo ciclista de Francia?' THEN '¿Cómo se llama el torneo ciclista de Francia?'
    WHEN 'Que seleccion gano mas Copas del Mundo de futbol hasta 2022?' THEN '¿Qué selección ganó más Copas del Mundo de fútbol hasta 2022?'
    WHEN 'Cuantos sets debe ganar un jugador para vencer en un partido de tenis masculino de Grand Slam?' THEN '¿Cuántos sets debe ganar un jugador para vencer en un partido de tenis masculino de Grand Slam?'
    WHEN 'En que deporte se consigue un home run?' THEN '¿En qué deporte se consigue un home run?'
    WHEN 'Que color de tarjeta expulsa a un jugador en futbol?' THEN '¿Qué color de tarjeta expulsa a un jugador en fútbol?'
    WHEN 'Cuantos jugadores forman un equipo de voleibol en cancha?' THEN '¿Cuántos jugadores forman un equipo de voleibol en cancha?'
    WHEN 'Que pais es famoso por el sumo?' THEN '¿Qué país es famoso por el sumo?'
    WHEN 'En que deporte se usa un puck?' THEN '¿En qué deporte se usa un puck?'
    WHEN 'Cuantas casillas tiene un tablero de ajedrez?' THEN '¿Cuántas casillas tiene un tablero de ajedrez?'
    WHEN 'Que atleta gano ocho medallas de oro en Beijing 2008?' THEN '¿Qué atleta ganó ocho medallas de oro en Beijing 2008?'
    WHEN 'Quien escribio Don Quijote de la Mancha?' THEN '¿Quién escribió Don Quijote de la Mancha?'
    WHEN 'Quien pinto La Gioconda?' THEN '¿Quién pintó La Gioconda?'
    WHEN 'Cual es la lengua original de La Divina Comedia?' THEN '¿Cuál es la lengua original de La Divina Comedia?'
    WHEN 'Que instrumento tiene teclas, cuerdas y martillos?' THEN '¿Qué instrumento tiene teclas, cuerdas y martillos?'
    WHEN 'Quien compuso la Novena Sinfonia?' THEN '¿Quién compuso la Novena Sinfonía?'
    WHEN 'Que estilo arquitectonico caracteriza a Notre Dame de Paris?' THEN '¿Qué estilo arquitectónico caracteriza a Notre Dame de París?'
    WHEN 'Quien escribio Cien anos de soledad?' THEN '¿Quién escribió Cien años de soledad?'
    WHEN 'Que movimiento artistico inicio Pablo Picasso junto a Georges Braque?' THEN '¿Qué movimiento artístico inició Pablo Picasso junto a Georges Braque?'
    WHEN 'Como se llama el arte de doblar papel?' THEN '¿Cómo se llama el arte de doblar papel?'
    WHEN 'Que premio reconoce obras de literatura en lengua espanola?' THEN '¿Qué premio reconoce obras de literatura en lengua española?'
    WHEN 'Quien escribio Romeo y Julieta?' THEN '¿Quién escribió Romeo y Julieta?'
    WHEN 'Que danza es tradicional de Argentina?' THEN '¿Qué danza es tradicional de Argentina?'
    WHEN 'Que museo alberga la Mona Lisa?' THEN '¿Qué museo alberga la Mona Lisa?'
    WHEN 'Que genero literario narra hechos imaginarios?' THEN '¿Qué género literario narra hechos imaginarios?'
    WHEN 'Como se llama una obra teatral de humor?' THEN '¿Cómo se llama una obra teatral de humor?'
    WHEN 'Que poeta chileno gano el Nobel de Literatura en 1971?' THEN '¿Qué poeta chileno ganó el Nobel de Literatura en 1971?'
    WHEN 'Que tecnica pictorica usa pequenos puntos de color?' THEN '¿Qué técnica pictórica usa pequeños puntos de color?'
    WHEN 'Que edificio es un simbolo de la ciudad de Pisa?' THEN '¿Qué edificio es un símbolo de la ciudad de Pisa?'
    WHEN 'Que idioma tiene mas hablantes nativos?' THEN '¿Qué idioma tiene más hablantes nativos?'
    WHEN 'Como se llama la narracion breve con moraleja?' THEN '¿Cómo se llama la narración breve con moraleja?'
    WHEN 'Cual es el planeta mas grande del sistema solar?' THEN '¿Cuál es el planeta más grande del sistema solar?'
    WHEN 'Que gas necesitan las plantas para la fotosintesis?' THEN '¿Qué gas necesitan las plantas para la fotosíntesis?'
    WHEN 'Cual es el simbolo quimico del oro?' THEN '¿Cuál es el símbolo químico del oro?'
    WHEN 'Cuantos huesos tiene aproximadamente un adulto?' THEN '¿Cuántos huesos tiene aproximadamente un adulto?'
    WHEN 'Que organo bombea la sangre?' THEN '¿Qué órgano bombea la sangre?'
    WHEN 'Como se llama el cambio de liquido a gas?' THEN '¿Cómo se llama el cambio de líquido a gas?'
    WHEN 'Que fuerza atrae los cuerpos hacia la Tierra?' THEN '¿Qué fuerza atrae los cuerpos hacia la Tierra?'
    WHEN 'Cual es la unidad basica de la vida?' THEN '¿Cuál es la unidad básica de la vida?'
    WHEN 'Que planeta es conocido como el planeta rojo?' THEN '¿Qué planeta es conocido como el planeta rojo?'
    WHEN 'Que instrumento mide la temperatura?' THEN '¿Qué instrumento mide la temperatura?'
    WHEN 'Que parte de la planta absorbe agua del suelo?' THEN '¿Qué parte de la planta absorbe agua del suelo?'
    WHEN 'Que metal es liquido a temperatura ambiente?' THEN '¿Qué metal es líquido a temperatura ambiente?'
    WHEN 'Cuantos lados tiene un hexagono?' THEN '¿Cuántos lados tiene un hexágono?'
    WHEN 'Que particula tiene carga negativa?' THEN '¿Qué partícula tiene carga negativa?'
    WHEN 'Como se llama el estudio de los terremotos?' THEN '¿Cómo se llama el estudio de los terremotos?'
    WHEN 'Que vitamina produce el cuerpo con la luz solar?' THEN '¿Qué vitamina produce el cuerpo con la luz solar?'
    WHEN 'Cual es la velocidad aproximada de la luz?' THEN '¿Cuál es la velocidad aproximada de la luz?'
    WHEN 'Que mamifero pone huevos?' THEN '¿Qué mamífero pone huevos?'
    WHEN 'Que capa protege a la Tierra de gran parte de la radiacion ultravioleta?' THEN '¿Qué capa protege a la Tierra de gran parte de la radiación ultravioleta?'
    WHEN 'Que tipo de energia produce el Sol?' THEN '¿Qué tipo de energía produce el Sol?'
    WHEN 'Cual es la capital de Francia?' THEN '¿Cuál es la capital de Francia?'
    WHEN 'En que continente esta Egipto?' THEN '¿En qué continente está Egipto?'
    WHEN 'Cual es el oceano mas grande?' THEN '¿Cuál es el océano más grande?'
    WHEN 'Cual es el rio mas largo de Sudamerica?' THEN '¿Cuál es el río más largo de Sudamérica?'
    WHEN 'Que pais tiene forma de bota?' THEN '¿Qué país tiene forma de bota?'
    WHEN 'Cual es la montana mas alta del mundo?' THEN '¿Cuál es la montaña más alta del mundo?'
    WHEN 'Cual es la capital de Japon?' THEN '¿Cuál es la capital de Japón?'
    WHEN 'Que desierto es el mas grande y calido del mundo?' THEN '¿Qué desierto es el más grande y cálido del mundo?'
    WHEN 'En que pais se encuentra Machu Picchu?' THEN '¿En qué país se encuentra Machu Picchu?'
    WHEN 'Cual es el pais mas extenso del mundo?' THEN '¿Cuál es el país más extenso del mundo?'
    WHEN 'Que linea divide la Tierra en hemisferios norte y sur?' THEN '¿Qué línea divide la Tierra en hemisferios norte y sur?'
    WHEN 'Cual es la capital de Australia?' THEN '¿Cuál es la capital de Australia?'
    WHEN 'Que pais tiene como capital a El Cairo?' THEN '¿Qué país tiene como capital a El Cairo?'
    WHEN 'Cual es la isla mas grande del mundo?' THEN '¿Cuál es la isla más grande del mundo?'
    WHEN 'En que cordillera se encuentra el Aconcagua?' THEN '¿En qué cordillera se encuentra el Aconcagua?'
    WHEN 'Cual es la capital de Brasil?' THEN '¿Cuál es la capital de Brasil?'
    WHEN 'Que mar separa Europa de Africa?' THEN '¿Qué mar separa Europa de África?'
    WHEN 'En que pais esta la ciudad de Petra?' THEN '¿En qué país está la ciudad de Petra?'
    WHEN 'Cual es el lago de agua dulce mas grande por superficie?' THEN '¿Cuál es el lago de agua dulce más grande por superficie?'
    WHEN 'Que pais comparte la Patagonia con Argentina?' THEN '¿Qué país comparte la Patagonia con Argentina?'
    ELSE enunciado
END
WHERE BINARY enunciado IN (
    'Quien fue el primer presidente de la Argentina?',
    'En que ano comenzo la Revolucion Francesa?',
    'Que civilizacion construyo Machu Picchu?',
    'Quien escribio El Principe?',
    'En que ano llego Cristobal Colon a America?',
    'Que imperio construyo el Coliseo Romano?',
    'Quien lidero la independencia de la India?',
    'En que ano cayo el Muro de Berlin?',
    'Que pueblo invento la democracia en Atenas?',
    'Quien fue conocido como el Libertador de America del Sur?',
    'Que faraon es famoso por su tumba hallada en 1922?',
    'En que pais ocurrio la Revolucion de Octubre de 1917?',
    'Quien fue la primera mujer en ganar un Premio Nobel?',
    'Que guerra termino con el Tratado de Versalles?',
    'Quien fue el primer emperador romano?',
    'Que acontecimiento se conmemora el 25 de mayo en Argentina?',
    'Quien lidero la campana libertadora de los Andes?',
    'En que siglo vivio Leonardo da Vinci?',
    'Que antigua ciudad fue sepultada por el Vesubio?',
    'Que documento limito el poder del rey ingles en 1215?',
    'Cuantos jugadores tiene un equipo de futbol en la cancha?',
    'En que deporte se usa un volante?',
    'Cuantos anillos tiene el simbolo olimpico?',
    'Que pais gano el Mundial de futbol 2022?',
    'Cuanto dura un partido de futbol reglamentario?',
    'En que deporte se disputa Wimbledon?',
    'Cuantos puntos vale un triple en basquet?',
    'Que pieza se mueve en forma de L en ajedrez?',
    'En que deporte se utiliza una tabla y una ola?',
    'Cuantas bases hay en el beisbol?',
    'Como se llama el torneo ciclista de Francia?',
    'Que seleccion gano mas Copas del Mundo de futbol hasta 2022?',
    'Cuantos sets debe ganar un jugador para vencer en un partido de tenis masculino de Grand Slam?',
    'En que deporte se consigue un home run?',
    'Que color de tarjeta expulsa a un jugador en futbol?',
    'Cuantos jugadores forman un equipo de voleibol en cancha?',
    'Que pais es famoso por el sumo?',
    'En que deporte se usa un puck?',
    'Cuantas casillas tiene un tablero de ajedrez?',
    'Que atleta gano ocho medallas de oro en Beijing 2008?',
    'Quien escribio Don Quijote de la Mancha?',
    'Quien pinto La Gioconda?',
    'Cual es la lengua original de La Divina Comedia?',
    'Que instrumento tiene teclas, cuerdas y martillos?',
    'Quien compuso la Novena Sinfonia?',
    'Que estilo arquitectonico caracteriza a Notre Dame de Paris?',
    'Quien escribio Cien anos de soledad?',
    'Que movimiento artistico inicio Pablo Picasso junto a Georges Braque?',
    'Como se llama el arte de doblar papel?',
    'Que premio reconoce obras de literatura en lengua espanola?',
    'Quien escribio Romeo y Julieta?',
    'Que danza es tradicional de Argentina?',
    'Que museo alberga la Mona Lisa?',
    'Que genero literario narra hechos imaginarios?',
    'Como se llama una obra teatral de humor?',
    'Que poeta chileno gano el Nobel de Literatura en 1971?',
    'Que tecnica pictorica usa pequenos puntos de color?',
    'Que edificio es un simbolo de la ciudad de Pisa?',
    'Que idioma tiene mas hablantes nativos?',
    'Como se llama la narracion breve con moraleja?',
    'Cual es el planeta mas grande del sistema solar?',
    'Que gas necesitan las plantas para la fotosintesis?',
    'Cual es el simbolo quimico del oro?',
    'Cuantos huesos tiene aproximadamente un adulto?',
    'Que organo bombea la sangre?',
    'Como se llama el cambio de liquido a gas?',
    'Que fuerza atrae los cuerpos hacia la Tierra?',
    'Cual es la unidad basica de la vida?',
    'Que planeta es conocido como el planeta rojo?',
    'Que instrumento mide la temperatura?',
    'Que parte de la planta absorbe agua del suelo?',
    'Que metal es liquido a temperatura ambiente?',
    'Cuantos lados tiene un hexagono?',
    'Que particula tiene carga negativa?',
    'Como se llama el estudio de los terremotos?',
    'Que vitamina produce el cuerpo con la luz solar?',
    'Cual es la velocidad aproximada de la luz?',
    'Que mamifero pone huevos?',
    'Que capa protege a la Tierra de gran parte de la radiacion ultravioleta?',
    'Que tipo de energia produce el Sol?',
    'Cual es la capital de Francia?',
    'En que continente esta Egipto?',
    'Cual es el oceano mas grande?',
    'Cual es el rio mas largo de Sudamerica?',
    'Que pais tiene forma de bota?',
    'Cual es la montana mas alta del mundo?',
    'Cual es la capital de Japon?',
    'Que desierto es el mas grande y calido del mundo?',
    'En que pais se encuentra Machu Picchu?',
    'Cual es el pais mas extenso del mundo?',
    'Que linea divide la Tierra en hemisferios norte y sur?',
    'Cual es la capital de Australia?',
    'Que pais tiene como capital a El Cairo?',
    'Cual es la isla mas grande del mundo?',
    'En que cordillera se encuentra el Aconcagua?',
    'Cual es la capital de Brasil?',
    'Que mar separa Europa de Africa?',
    'En que pais esta la ciudad de Petra?',
    'Cual es el lago de agua dulce mas grande por superficie?',
    'Que pais comparte la Patagonia con Argentina?'
);

-- 2) Opciones, solo de las preguntas corregidas arriba.
UPDATE opcion
SET texto = CASE BINARY texto
    WHEN 'Acropolis' THEN 'Acrópolis'
    WHEN 'Africa' THEN 'África'
    WHEN 'Akenaton' THEN 'Akenatón'
    WHEN 'Anemometro' THEN 'Anemómetro'
    WHEN 'Antonio Lopez de Santa Anna' THEN 'Antonio López de Santa Anna'
    WHEN 'Aristoteles' THEN 'Aristóteles'
    WHEN 'Artico' THEN 'Ártico'
    WHEN 'Astronomia' THEN 'Astronomía'
    WHEN 'Atlantico' THEN 'Atlántico'
    WHEN 'Atomo' THEN 'Átomo'
    WHEN 'Badminton' THEN 'Bádminton'
    WHEN 'Baltico' THEN 'Báltico'
    WHEN 'Barometro' THEN 'Barómetro'
    WHEN 'Batalla de Maipu' THEN 'Batalla de Maipú'
    WHEN 'Beisbol' THEN 'Béisbol'
    WHEN 'Biografia' THEN 'Biografía'
    WHEN 'Caligrafia' THEN 'Caligrafía'
    WHEN 'Canada' THEN 'Canadá'
    WHEN 'Celula' THEN 'Célula'
    WHEN 'Chino mandarin' THEN 'Chino mandarín'
    WHEN 'Circulo Polar Artico' THEN 'Círculo Polar Ártico'
    WHEN 'Codigo Napoleonico' THEN 'Código Napoleónico'
    WHEN 'Condensacion' THEN 'Condensación'
    WHEN 'Corazon' THEN 'Corazón'
    WHEN 'Creacion de la bandera' THEN 'Creación de la bandera'
    WHEN 'Cronica' THEN 'Crónica'
    WHEN 'Declaracion de la Independencia' THEN 'Declaración de la Independencia'
    WHEN 'Delfin' THEN 'Delfín'
    WHEN 'Dioxido de carbono' THEN 'Dióxido de carbono'
    WHEN 'Ecologia' THEN 'Ecología'
    WHEN 'Edicto de Milan' THEN 'Edicto de Milán'
    WHEN 'Electron' THEN 'Electrón'
    WHEN 'Elegia' THEN 'Elegía'
    WHEN 'Energia eolica' THEN 'Energía eólica'
    WHEN 'Energia geotermica' THEN 'Energía geotérmica'
    WHEN 'Energia nuclear' THEN 'Energía nuclear'
    WHEN 'Energia quimica' THEN 'Energía química'
    WHEN 'Espanol' THEN 'Español'
    WHEN 'Evaporacion' THEN 'Evaporación'
    WHEN 'Fabula' THEN 'Fábula'
    WHEN 'Ficcion' THEN 'Ficción'
    WHEN 'Frances' THEN 'Francés'
    WHEN 'Friccion' THEN 'Fricción'
    WHEN 'Fusion' THEN 'Fusión'
    WHEN 'Futbol' THEN 'Fútbol'
    WHEN 'Gabriel Garcia Marquez' THEN 'Gabriel García Márquez'
    WHEN 'Genetica' THEN 'Genética'
    WHEN 'Gotico' THEN 'Gótico'
    WHEN 'Guerra Fria' THEN 'Guerra Fría'
    WHEN 'Higado' THEN 'Hígado'
    WHEN 'Higrometro' THEN 'Higrómetro'
    WHEN 'Indico' THEN 'Índico'
    WHEN 'Ingles' THEN 'Inglés'
    WHEN 'Japon' THEN 'Japón'
    WHEN 'Jose de San Martin' THEN 'José de San Martín'
    WHEN 'Julio Cesar' THEN 'Julio César'
    WHEN 'Julio Cortazar' THEN 'Julio Cortázar'
    WHEN 'Jupiter' THEN 'Júpiter'
    WHEN 'Latin' THEN 'Latín'
    WHEN 'Martin Guemes' THEN 'Martín Güemes'
    WHEN 'Mediterraneo' THEN 'Mediterráneo'
    WHEN 'Molecula' THEN 'Molécula'
    WHEN 'Murcielago' THEN 'Murciélago'
    WHEN 'Natacion' THEN 'Natación'
    WHEN 'Neoclasico' THEN 'Neoclásico'
    WHEN 'Neron' THEN 'Nerón'
    WHEN 'Neutron' THEN 'Neutrón'
    WHEN 'Nicolas Maquiavelo' THEN 'Nicolás Maquiavelo'
    WHEN 'Nitrogeno' THEN 'Nitrógeno'
    WHEN 'Oceania' THEN 'Oceanía'
    WHEN 'Oxigeno' THEN 'Oxígeno'
    WHEN 'Pacifico' THEN 'Pacífico'
    WHEN 'Parana' THEN 'Paraná'
    WHEN 'Paris' THEN 'París'
    WHEN 'Paris-Roubaix' THEN 'París-Roubaix'
    WHEN 'Peru' THEN 'Perú'
    WHEN 'Platon' THEN 'Platón'
    WHEN 'Presion' THEN 'Presión'
    WHEN 'Proton' THEN 'Protón'
    WHEN 'Pulmon' THEN 'Pulmón'
    WHEN 'Raiz' THEN 'Raíz'
    WHEN 'Ramses II' THEN 'Ramsés II'
    WHEN 'Revolucion de Mayo' THEN 'Revolución de Mayo'
    WHEN 'Rinon' THEN 'Riñón'
    WHEN 'Rio de Janeiro' THEN 'Río de Janeiro'
    WHEN 'Romanico' THEN 'Románico'
    WHEN 'Sao Paulo' THEN 'São Paulo'
    WHEN 'Simon Bolivar' THEN 'Simón Bolívar'
    WHEN 'Sismologia' THEN 'Sismología'
    WHEN 'Solidificacion' THEN 'Solidificación'
    WHEN 'Termometro' THEN 'Termómetro'
    WHEN 'Tomas Moro' THEN 'Tomás Moro'
    WHEN 'Trópico de Cancer' THEN 'Trópico de Cáncer'
    WHEN 'Tunez' THEN 'Túnez'
    WHEN 'Turquia' THEN 'Turquía'
    WHEN 'Tutankamon' THEN 'Tutankamón'
    WHEN 'Violin' THEN 'Violín'
    WHEN 'Vuelta a Espana' THEN 'Vuelta a España'
    ELSE texto
END
WHERE BINARY texto IN (
    'Acropolis',
    'Africa',
    'Akenaton',
    'Anemometro',
    'Antonio Lopez de Santa Anna',
    'Aristoteles',
    'Artico',
    'Astronomia',
    'Atlantico',
    'Atomo',
    'Badminton',
    'Baltico',
    'Barometro',
    'Batalla de Maipu',
    'Beisbol',
    'Biografia',
    'Caligrafia',
    'Canada',
    'Celula',
    'Chino mandarin',
    'Circulo Polar Artico',
    'Codigo Napoleonico',
    'Condensacion',
    'Corazon',
    'Creacion de la bandera',
    'Cronica',
    'Declaracion de la Independencia',
    'Delfin',
    'Dioxido de carbono',
    'Ecologia',
    'Edicto de Milan',
    'Electron',
    'Elegia',
    'Energia eolica',
    'Energia geotermica',
    'Energia nuclear',
    'Energia quimica',
    'Espanol',
    'Evaporacion',
    'Fabula',
    'Ficcion',
    'Frances',
    'Friccion',
    'Fusion',
    'Futbol',
    'Gabriel Garcia Marquez',
    'Genetica',
    'Gotico',
    'Guerra Fria',
    'Higado',
    'Higrometro',
    'Indico',
    'Ingles',
    'Japon',
    'Jose de San Martin',
    'Julio Cesar',
    'Julio Cortazar',
    'Jupiter',
    'Latin',
    'Martin Guemes',
    'Mediterraneo',
    'Molecula',
    'Murcielago',
    'Natacion',
    'Neoclasico',
    'Neron',
    'Neutron',
    'Nicolas Maquiavelo',
    'Nitrogeno',
    'Oceania',
    'Oxigeno',
    'Pacifico',
    'Parana',
    'Paris',
    'Paris-Roubaix',
    'Peru',
    'Platon',
    'Presion',
    'Proton',
    'Pulmon',
    'Raiz',
    'Ramses II',
    'Revolucion de Mayo',
    'Rinon',
    'Rio de Janeiro',
    'Romanico',
    'Sao Paulo',
    'Simon Bolivar',
    'Sismologia',
    'Solidificacion',
    'Termometro',
    'Tomas Moro',
    'Trópico de Cancer',
    'Tunez',
    'Turquia',
    'Tutankamon',
    'Violin',
    'Vuelta a Espana'
)
AND pregunta_id IN (
    SELECT id FROM pregunta WHERE BINARY enunciado IN (
    '¿Quién fue el primer presidente de la Argentina?',
    '¿En qué año comenzó la Revolución Francesa?',
    '¿Qué civilización construyó Machu Picchu?',
    '¿Quién escribió El Príncipe?',
    '¿En qué año llegó Cristóbal Colón a América?',
    '¿Qué imperio construyó el Coliseo Romano?',
    '¿Quién lideró la independencia de la India?',
    '¿En qué año cayó el Muro de Berlín?',
    '¿Qué pueblo inventó la democracia en Atenas?',
    '¿Quién fue conocido como el Libertador de América del Sur?',
    '¿Qué faraón es famoso por su tumba hallada en 1922?',
    '¿En qué país ocurrió la Revolución de Octubre de 1917?',
    '¿Quién fue la primera mujer en ganar un Premio Nobel?',
    '¿Qué guerra terminó con el Tratado de Versalles?',
    '¿Quién fue el primer emperador romano?',
    '¿Qué acontecimiento se conmemora el 25 de mayo en Argentina?',
    '¿Quién lideró la campaña libertadora de los Andes?',
    '¿En qué siglo vivió Leonardo da Vinci?',
    '¿Qué antigua ciudad fue sepultada por el Vesubio?',
    '¿Qué documento limitó el poder del rey inglés en 1215?',
    '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?',
    '¿En qué deporte se usa un volante?',
    '¿Cuántos anillos tiene el símbolo olímpico?',
    '¿Qué país ganó el Mundial de fútbol 2022?',
    '¿Cuánto dura un partido de fútbol reglamentario?',
    '¿En qué deporte se disputa Wimbledon?',
    '¿Cuántos puntos vale un triple en básquet?',
    '¿Qué pieza se mueve en forma de L en ajedrez?',
    '¿En qué deporte se utiliza una tabla y una ola?',
    '¿Cuántas bases hay en el béisbol?',
    '¿Cómo se llama el torneo ciclista de Francia?',
    '¿Qué selección ganó más Copas del Mundo de fútbol hasta 2022?',
    '¿Cuántos sets debe ganar un jugador para vencer en un partido de tenis masculino de Grand Slam?',
    '¿En qué deporte se consigue un home run?',
    '¿Qué color de tarjeta expulsa a un jugador en fútbol?',
    '¿Cuántos jugadores forman un equipo de voleibol en cancha?',
    '¿Qué país es famoso por el sumo?',
    '¿En qué deporte se usa un puck?',
    '¿Cuántas casillas tiene un tablero de ajedrez?',
    '¿Qué atleta ganó ocho medallas de oro en Beijing 2008?',
    '¿Quién escribió Don Quijote de la Mancha?',
    '¿Quién pintó La Gioconda?',
    '¿Cuál es la lengua original de La Divina Comedia?',
    '¿Qué instrumento tiene teclas, cuerdas y martillos?',
    '¿Quién compuso la Novena Sinfonía?',
    '¿Qué estilo arquitectónico caracteriza a Notre Dame de París?',
    '¿Quién escribió Cien años de soledad?',
    '¿Qué movimiento artístico inició Pablo Picasso junto a Georges Braque?',
    '¿Cómo se llama el arte de doblar papel?',
    '¿Qué premio reconoce obras de literatura en lengua española?',
    '¿Quién escribió Romeo y Julieta?',
    '¿Qué danza es tradicional de Argentina?',
    '¿Qué museo alberga la Mona Lisa?',
    '¿Qué género literario narra hechos imaginarios?',
    '¿Cómo se llama una obra teatral de humor?',
    '¿Qué poeta chileno ganó el Nobel de Literatura en 1971?',
    '¿Qué técnica pictórica usa pequeños puntos de color?',
    '¿Qué edificio es un símbolo de la ciudad de Pisa?',
    '¿Qué idioma tiene más hablantes nativos?',
    '¿Cómo se llama la narración breve con moraleja?',
    '¿Cuál es el planeta más grande del sistema solar?',
    '¿Qué gas necesitan las plantas para la fotosíntesis?',
    '¿Cuál es el símbolo químico del oro?',
    '¿Cuántos huesos tiene aproximadamente un adulto?',
    '¿Qué órgano bombea la sangre?',
    '¿Cómo se llama el cambio de líquido a gas?',
    '¿Qué fuerza atrae los cuerpos hacia la Tierra?',
    '¿Cuál es la unidad básica de la vida?',
    '¿Qué planeta es conocido como el planeta rojo?',
    '¿Qué instrumento mide la temperatura?',
    '¿Qué parte de la planta absorbe agua del suelo?',
    '¿Qué metal es líquido a temperatura ambiente?',
    '¿Cuántos lados tiene un hexágono?',
    '¿Qué partícula tiene carga negativa?',
    '¿Cómo se llama el estudio de los terremotos?',
    '¿Qué vitamina produce el cuerpo con la luz solar?',
    '¿Cuál es la velocidad aproximada de la luz?',
    '¿Qué mamífero pone huevos?',
    '¿Qué capa protege a la Tierra de gran parte de la radiación ultravioleta?',
    '¿Qué tipo de energía produce el Sol?',
    '¿Cuál es la capital de Francia?',
    '¿En qué continente está Egipto?',
    '¿Cuál es el océano más grande?',
    '¿Cuál es el río más largo de Sudamérica?',
    '¿Qué país tiene forma de bota?',
    '¿Cuál es la montaña más alta del mundo?',
    '¿Cuál es la capital de Japón?',
    '¿Qué desierto es el más grande y cálido del mundo?',
    '¿En qué país se encuentra Machu Picchu?',
    '¿Cuál es el país más extenso del mundo?',
    '¿Qué línea divide la Tierra en hemisferios norte y sur?',
    '¿Cuál es la capital de Australia?',
    '¿Qué país tiene como capital a El Cairo?',
    '¿Cuál es la isla más grande del mundo?',
    '¿En qué cordillera se encuentra el Aconcagua?',
    '¿Cuál es la capital de Brasil?',
    '¿Qué mar separa Europa de África?',
    '¿En qué país está la ciudad de Petra?',
    '¿Cuál es el lago de agua dulce más grande por superficie?',
    '¿Qué país comparte la Patagonia con Argentina?'
    )
);

-- 3) Copias que quedaron repetidas: se da de baja la de mayor id si ya hay otra activa igual.
UPDATE pregunta d
    JOIN pregunta o ON BINARY o.enunciado = BINARY d.enunciado AND o.id < d.id AND o.estado = 'activa'
    SET d.estado = 'baja'
WHERE d.estado = 'activa' AND d.creada_por IS NULL;

-- Verificacion (opcional): lista las preguntas cargadas por el sistema que siguen sin el signo de apertura.
-- Despues de esta migration deberia devolver 0 filas.
-- SELECT id, enunciado FROM pregunta WHERE creada_por IS NULL AND enunciado NOT LIKE '¿%';

-- Y esta lista las preguntas activas con enunciado repetido. Tambien deberia devolver 0 filas.
-- SELECT enunciado, COUNT(*) AS veces FROM pregunta WHERE estado = 'activa' GROUP BY enunciado HAVING COUNT(*) > 1;
