-- Cambia el año de nacimiento por la fecha completa.
ALTER TABLE usuario
    CHANGE COLUMN anio_nacimiento fecha_nacimiento DATE NOT NULL;
