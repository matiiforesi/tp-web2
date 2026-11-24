-- 100 preguntas iniciales para Apuesta y Respuesta.
-- Requiere juego_preguntas.sql y las categorias 1 a 5.
-- Es idempotente: no duplica preguntas ni opciones existentes.
USE juego_preguntas;

-- Normaliza el nombre de la categoria si la carga anterior quedo con texto mal codificado.
UPDATE categoria SET nombre = 'Geografía' WHERE id = 5;

DROP TEMPORARY TABLE IF EXISTS carga_preguntas_100;
CREATE TEMPORARY TABLE carga_preguntas_100 (
    categoria VARCHAR(40) NOT NULL,
    enunciado VARCHAR(500) NOT NULL,
    correcta VARCHAR(255) NOT NULL,
    opcion_b VARCHAR(255) NOT NULL,
    opcion_c VARCHAR(255) NOT NULL,
    opcion_d VARCHAR(255) NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4;

INSERT INTO carga_preguntas_100 VALUES
('Historia','Quien fue el primer presidente de la Argentina?','Bernardino Rivadavia','Julio A. Roca','Domingo F. Sarmiento','Manuel Belgrano'),
('Historia','En que ano comenzo la Revolucion Francesa?','1789','1810','1776','1804'),
('Historia','Que civilizacion construyo Machu Picchu?','Inca','Maya','Azteca','Romana'),
('Historia','Quien escribio El Principe?','Nicolas Maquiavelo','Platon','Aristoteles','Tomas Moro'),
('Historia','En que ano llego Cristobal Colon a America?','1492','1453','1512','1607'),
('Historia','Que imperio construyo el Coliseo Romano?','Imperio Romano','Imperio Persa','Imperio Inca','Imperio Otomano'),
('Historia','Quien lidero la independencia de la India?','Mahatma Gandhi','Nelson Mandela','Winston Churchill','Sun Yat-sen'),
('Historia','En que ano cayo el Muro de Berlin?','1989','1975','1991','1961'),
('Historia','Que pueblo invento la democracia en Atenas?','Los griegos','Los egipcios','Los fenicios','Los vikingos'),
('Historia','Quien fue conocido como el Libertador de America del Sur?','Simon Bolivar','Jose de San Martin','Miguel Hidalgo','Antonio Lopez de Santa Anna'),
('Historia','Que faraon es famoso por su tumba hallada en 1922?','Tutankamon','Ramses II','Cleopatra','Akenaton'),
('Historia','En que pais ocurrio la Revolucion de Octubre de 1917?','Rusia','Francia','China','Alemania'),
('Historia','Quien fue la primera mujer en ganar un Premio Nobel?','Marie Curie','Ada Lovelace','Rosalind Franklin','Jane Goodall'),
('Historia','Que guerra termino con el Tratado de Versalles?','Primera Guerra Mundial','Segunda Guerra Mundial','Guerra de Crimea','Guerra Fria'),
('Historia','Quien fue el primer emperador romano?','Augusto','Julio Cesar','Neron','Trajano'),
('Historia','Que acontecimiento se conmemora el 25 de mayo en Argentina?','Revolucion de Mayo','Declaracion de la Independencia','Batalla de Maipu','Creacion de la bandera'),
('Historia','Quien lidero la campana libertadora de los Andes?','Jose de San Martin','Manuel Belgrano','Martin Guemes','Juan Manuel de Rosas'),
('Historia','En que siglo vivio Leonardo da Vinci?','Siglo XV','Siglo XII','Siglo XVIII','Siglo XX'),
('Historia','Que antigua ciudad fue sepultada por el Vesubio?','Pompeya','Esparta','Cartago','Troya'),
('Historia','Que documento limito el poder del rey ingles en 1215?','Carta Magna','Codigo Napoleonico','Edicto de Milan','Paz de Augsburgo'),
('Deportes','Cuantos jugadores tiene un equipo de futbol en la cancha?','11','9','10','12'),
('Deportes','En que deporte se usa un volante?','Badminton','Rugby','Beisbol','Natacion'),
('Deportes','Cuantos anillos tiene el simbolo olimpico?','5','4','6','7'),
('Deportes','Que pais gano el Mundial de futbol 2022?','Argentina','Francia','Brasil','Croacia'),
('Deportes','Cuanto dura un partido de futbol reglamentario?','90 minutos','60 minutos','80 minutos','120 minutos'),
('Deportes','En que deporte se disputa Wimbledon?','Tenis','Golf','Cricket','Hockey'),
('Deportes','Cuantos puntos vale un triple en basquet?','3','2','4','1'),
('Deportes','Que pieza se mueve en forma de L en ajedrez?','Caballo','Alfil','Torre','Rey'),
('Deportes','En que deporte se utiliza una tabla y una ola?','Surf','Esgrima','Ciclismo','Atletismo'),
('Deportes','Cuantas bases hay en el beisbol?','4','3','5','6'),
('Deportes','Como se llama el torneo ciclista de Francia?','Tour de France','Giro de Italia','Vuelta a Espana','Paris-Roubaix'),
('Deportes','Que seleccion gano mas Copas del Mundo de futbol hasta 2022?','Brasil','Alemania','Italia','Argentina'),
('Deportes','Cuantos sets debe ganar un jugador para vencer en un partido de tenis masculino de Grand Slam?','3','2','4','5'),
('Deportes','En que deporte se consigue un home run?','Beisbol','Futbol','Rugby','Voleibol'),
('Deportes','Que color de tarjeta expulsa a un jugador en futbol?','Roja','Azul','Verde','Amarilla'),
('Deportes','Cuantos jugadores forman un equipo de voleibol en cancha?','6','5','7','8'),
('Deportes','Que pais es famoso por el sumo?','Japon','China','Corea del Sur','Mongolia'),
('Deportes','En que deporte se usa un puck?','Hockey sobre hielo','Polo','Handball','Waterpolo'),
('Deportes','Cuantas casillas tiene un tablero de ajedrez?','64','48','81','100'),
('Deportes','Que atleta gano ocho medallas de oro en Beijing 2008?','Michael Phelps','Usain Bolt','Carl Lewis','Mark Spitz'),
('Cultura','Quien escribio Don Quijote de la Mancha?','Miguel de Cervantes','William Shakespeare','Jorge Luis Borges','Gabriel Garcia Marquez'),
('Cultura','Quien pinto La Gioconda?','Leonardo da Vinci','Vincent van Gogh','Pablo Picasso','Rafael'),
('Cultura','Cual es la lengua original de La Divina Comedia?','Italiano','Latin','Frances','Espanol'),
('Cultura','Que instrumento tiene teclas, cuerdas y martillos?','Piano','Violin','Flauta','Trompeta'),
('Cultura','Quien compuso la Novena Sinfonia?','Ludwig van Beethoven','Wolfgang Mozart','Johann Bach','Antonio Vivaldi'),
('Cultura','Que estilo arquitectonico caracteriza a Notre Dame de Paris?','Gotico','Barroco','Romanico','Neoclasico'),
('Cultura','Quien escribio Cien anos de soledad?','Gabriel Garcia Marquez','Mario Vargas Llosa','Julio Cortazar','Pablo Neruda'),
('Cultura','Que movimiento artistico inicio Pablo Picasso junto a Georges Braque?','Cubismo','Impresionismo','Surrealismo','Futurismo'),
('Cultura','Como se llama el arte de doblar papel?','Origami','Caligrafia','Ikebana','Haiku'),
('Cultura','Que premio reconoce obras de literatura en lengua espanola?','Premio Cervantes','Premio Oscar','Premio Pritzker','Premio Turing'),
('Cultura','Quien escribio Romeo y Julieta?','William Shakespeare','Charles Dickens','Homero','Oscar Wilde'),
('Cultura','Que danza es tradicional de Argentina?','Tango','Samba','Flamenco','Polka'),
('Cultura','Que museo alberga la Mona Lisa?','Louvre','Prado','Hermitage','Uffizi'),
('Cultura','Que genero literario narra hechos imaginarios?','Ficcion','Ensayo','Biografia','Cronica'),
('Cultura','Como se llama una obra teatral de humor?','Comedia','Tragedia','Oda','Elegia'),
('Cultura','Que poeta chileno gano el Nobel de Literatura en 1971?','Pablo Neruda','Gabriela Mistral','Octavio Paz','Mario Benedetti'),
('Cultura','Que tecnica pictorica usa pequenos puntos de color?','Puntillismo','Grabado','Fresco','Collage'),
('Cultura','Que edificio es un simbolo de la ciudad de Pisa?','Torre inclinada','Coliseo','Big Ben','Acropolis'),
('Cultura','Que idioma tiene mas hablantes nativos?','Chino mandarin','Ingles','Espanol','Hindi'),
('Cultura','Como se llama la narracion breve con moraleja?','Fabula','Novela','Epopeya','Soneto'),
('Ciencia','Cual es el planeta mas grande del sistema solar?','Jupiter','Saturno','Neptuno','Tierra'),
('Ciencia','Que gas necesitan las plantas para la fotosintesis?','Dioxido de carbono','Oxigeno','Nitrogeno','Helio'),
('Ciencia','Cual es el simbolo quimico del oro?','Au','Ag','Fe','O'),
('Ciencia','Cuantos huesos tiene aproximadamente un adulto?','206','180','250','312'),
('Ciencia','Que organo bombea la sangre?','Corazon','Pulmon','Higado','Rinon'),
('Ciencia','Como se llama el cambio de liquido a gas?','Evaporacion','Condensacion','Fusion','Solidificacion'),
('Ciencia','Que fuerza atrae los cuerpos hacia la Tierra?','Gravedad','Electricidad','Friccion','Presion'),
('Ciencia','Cual es la unidad basica de la vida?','Celula','Atomo','Tejido','Molecula'),
('Ciencia','Que planeta es conocido como el planeta rojo?','Marte','Venus','Mercurio','Urano'),
('Ciencia','Que instrumento mide la temperatura?','Termometro','Barometro','Higrometro','Anemometro'),
('Ciencia','Que parte de la planta absorbe agua del suelo?','Raiz','Flor','Fruto','Tallo'),
('Ciencia','Que metal es liquido a temperatura ambiente?','Mercurio','Hierro','Cobre','Aluminio'),
('Ciencia','Cuantos lados tiene un hexagono?','6','5','7','8'),
('Ciencia','Que particula tiene carga negativa?','Electron','Proton','Neutron','Quark'),
('Ciencia','Como se llama el estudio de los terremotos?','Sismologia','Astronomia','Ecologia','Genetica'),
('Ciencia','Que vitamina produce el cuerpo con la luz solar?','Vitamina D','Vitamina C','Vitamina B12','Vitamina K'),
('Ciencia','Cual es la velocidad aproximada de la luz?','300000 km/s','3000 km/s','300 km/s','3000000 km/s'),
('Ciencia','Que mamifero pone huevos?','Ornitorrinco','Delfin','Murcielago','Canguro'),
('Ciencia','Que capa protege a la Tierra de gran parte de la radiacion ultravioleta?','Capa de ozono','Troposfera','Ionosfera','Corteza'),
('Ciencia','Que tipo de energia produce el Sol?','Energia nuclear','Energia eolica','Energia geotermica','Energia quimica'),
('Geografia','Cual es la capital de Francia?','Paris','Roma','Madrid','Lisboa'),
('Geografia','En que continente esta Egipto?','Africa','Asia','Europa','Oceania'),
('Geografia','Cual es el oceano mas grande?','Pacifico','Atlantico','Indico','Artico'),
('Geografia','Cual es el rio mas largo de Sudamerica?','Amazonas','Parana','Orinoco','Uruguay'),
('Geografia','Que pais tiene forma de bota?','Italia','Grecia','Portugal','Chile'),
('Geografia','Cual es la montana mas alta del mundo?','Everest','Aconcagua','Kilimanjaro','Mont Blanc'),
('Geografia','Cual es la capital de Japon?','Tokio','Kioto','Osaka','Hiroshima'),
('Geografia','Que desierto es el mas grande y calido del mundo?','Sahara','Gobi','Atacama','Kalahari'),
('Geografia','En que pais se encuentra Machu Picchu?','Peru','Bolivia','Ecuador','Colombia'),
('Geografia','Cual es el pais mas extenso del mundo?','Rusia','Canada','China','Estados Unidos'),
('Geografia','Que linea divide la Tierra en hemisferios norte y sur?','Ecuador','Meridiano de Greenwich','Trópico de Cancer','Circulo Polar Artico'),
('Geografia','Cual es la capital de Australia?','Canberra','Sydney','Melbourne','Perth'),
('Geografia','Que pais tiene como capital a El Cairo?','Egipto','Jordania','Marruecos','Tunez'),
('Geografia','Cual es la isla mas grande del mundo?','Groenlandia','Madagascar','Borneo','Nueva Guinea'),
('Geografia','En que cordillera se encuentra el Aconcagua?','Andes','Alpes','Himalaya','Rocosas'),
('Geografia','Cual es la capital de Brasil?','Brasilia','Rio de Janeiro','Sao Paulo','Salvador'),
('Geografia','Que mar separa Europa de Africa?','Mediterraneo','Caribe','Baltico','Rojo'),
('Geografia','En que pais esta la ciudad de Petra?','Jordania','Israel','Turquia','Siria'),
('Geografia','Cual es el lago de agua dulce mas grande por superficie?','Lago Superior','Lago Titicaca','Lago Victoria','Lago Baikal'),
('Geografia','Que pais comparte la Patagonia con Argentina?','Chile','Uruguay','Paraguay','Bolivia');

INSERT INTO pregunta (categoria_id, enunciado, estado)
SELECT c.id, q.enunciado, 'activa'
FROM carga_preguntas_100 q
         JOIN categoria c ON c.nombre = q.categoria OR (q.categoria = 'Geografia' AND c.id = 5)
WHERE NOT EXISTS (SELECT 1 FROM pregunta p WHERE p.enunciado = q.enunciado);

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, q.correcta, 1
FROM carga_preguntas_100 q JOIN pregunta p ON p.enunciado = q.enunciado
WHERE NOT EXISTS (SELECT 1 FROM opcion o WHERE o.pregunta_id = p.id);

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, q.opcion_b, 0
FROM carga_preguntas_100 q JOIN pregunta p ON p.enunciado = q.enunciado
WHERE NOT EXISTS (SELECT 1 FROM opcion o WHERE o.pregunta_id = p.id AND o.texto = q.opcion_b);

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, q.opcion_c, 0
FROM carga_preguntas_100 q JOIN pregunta p ON p.enunciado = q.enunciado
WHERE NOT EXISTS (SELECT 1 FROM opcion o WHERE o.pregunta_id = p.id AND o.texto = q.opcion_c);

INSERT INTO opcion (pregunta_id, texto, es_correcta)
SELECT p.id, q.opcion_d, 0
FROM carga_preguntas_100 q JOIN pregunta p ON p.enunciado = q.enunciado
WHERE NOT EXISTS (SELECT 1 FROM opcion o WHERE o.pregunta_id = p.id AND o.texto = q.opcion_d);

DROP TEMPORARY TABLE carga_preguntas_100;
