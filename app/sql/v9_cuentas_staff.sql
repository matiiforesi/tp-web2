USE juego_preguntas;

-- Cuentas locales de acceso para edicion y administracion.
-- La insercion es idempotente y no cambia cuentas que ya existan.

INSERT INTO usuario
    (rol_id, nombre_completo, fecha_nacimiento, sexo, pais_codigo, pais, ciudad,
     latitud, longitud, email, usuario, password_hash, cuenta_validada)
SELECT r.id, 'Editor Apuesta y Respuesta', '1990-01-01', 'N', 'AR', 'Argentina', 'Buenos Aires',
       -34.6037, -58.3816, 'editor@apuesta-respuesta.local', 'editor_apuesta',
       '$2y$10$ym4Sv/cMnLi6aUdU3FIUHu2KiNQVty9UWbwjLvwuCsEGV9JZ6Z.fK', 1
FROM rol r
WHERE r.nombre = 'editor'
  AND NOT EXISTS (
      SELECT 1 FROM usuario u
      WHERE u.usuario = 'editor_apuesta' OR u.email = 'editor@apuesta-respuesta.local'
  );

INSERT INTO usuario
    (rol_id, nombre_completo, fecha_nacimiento, sexo, pais_codigo, pais, ciudad,
     latitud, longitud, email, usuario, password_hash, cuenta_validada)
SELECT r.id, 'Administrador Apuesta y Respuesta', '1990-01-01', 'N', 'AR', 'Argentina', 'Buenos Aires',
       -34.6037, -58.3816, 'admin@apuesta-respuesta.local', 'admin_apuesta',
       '$2y$10$1R4Zc8EEM4TxZWgpkGZRWu0fRD7asSGN/8Vi8B99jFNjZJp9el4Ji', 1
FROM rol r
WHERE r.nombre = 'administrador'
  AND NOT EXISTS (
      SELECT 1 FROM usuario u
      WHERE u.usuario = 'admin_apuesta' OR u.email = 'admin@apuesta-respuesta.local'
  );
