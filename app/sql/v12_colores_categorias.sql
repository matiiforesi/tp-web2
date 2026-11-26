-- v12: colores de categorías alineados con la convención más común (estilo Trivial Pursuit).
-- Historia amarillo/dorado, Deportes naranja, Geografía azul, Ciencia verde, Cultura violeta.
-- Idempotente: se puede correr más de una vez. Correr en local y en InfinityFree.
UPDATE categoria SET color = '#E67E22' WHERE nombre = 'Deportes';
UPDATE categoria SET color = '#2980B9' WHERE nombre = 'Geografía';
UPDATE categoria SET color = '#2E8B57' WHERE nombre = 'Ciencia';
