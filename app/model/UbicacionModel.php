<?php
/** Este model es provisional hasta que se sepa cómo se implementa correctamente el mapa. */
namespace app\model;

class UbicacionModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function obtenerPaises()
    {
        return $this->db->queryAll("SELECT id, nombre FROM pais ORDER BY nombre");
    }

    public function obtenerCiudades()
    {
        return $this->db->queryAll(
            "SELECT id, pais_id, nombre, latitud, longitud FROM ciudad ORDER BY nombre"
        );
    }

    /** La ciudad elegida tiene que existir Y pertenecer al pais elegido. */
    public function ciudadPerteneceAPais($ciudadId, $paisId): bool
    {
        $fila = $this->db->queryOne(
            "SELECT id FROM ciudad WHERE id = ? AND pais_id = ?",
            [$ciudadId, $paisId]
        );
        return $fila !== null;
    }
}
