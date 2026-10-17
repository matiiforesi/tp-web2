<?php

class MyDatabase
{
    private $conexion;

    public function __construct($host, $user, $pass, $db)
    {
        $this->conexion = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    /**
     * Todos los metodos aceptan parametros opcionales para usar prepared statements.
     * Siempre que haya datos del usuario en la consulta, usar "?" y pasarlos en $params:
     *
     *   $db->queryOne("SELECT * FROM usuario WHERE email = ?", [$email]);
     *
     * Los parametros NO se escriben en el log (pueden ser contrasenas o datos personales).
     */
    public function queryOne(string $sql, array $params = [])
    {
        $fila = $this->ejecutar($sql, $params)->fetch();
        return $fila === false ? null : $fila;
    }

    public function queryAll(string $sql, array $params = [])
    {
        return $this->ejecutar($sql, $params)->fetchAll();
    }

    public function execute(string $sql, array $params = [])
    {
        return $this->ejecutar($sql, $params)->rowCount();
    }

    public function lastInsertId()
    {
        return $this->conexion->lastInsertId();
    }

    private function ejecutar(string $sql, array $params): PDOStatement
    {
        Log::info($sql);
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
