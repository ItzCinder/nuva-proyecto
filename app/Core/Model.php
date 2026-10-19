<?php

namespace app\Models;

use Exception;

abstract class Model
{
    protected \PDO $database;

    public function __construct()
    {
        try {
            /*Singleton
            getConnection() comparte una conexión PDO entre los modelos.
            */
            $this->database = \Database::getConnection();
        } catch (Exception $exception) {
            throw new Exception("Error al conectar a la base de datos: " . $exception->getMessage());
        }
    }

    // Todos los modelos pueden ejecutar consultas preparadas de la misma manera.
    protected function query(string $sql, array $values = []): \PDOStatement
    {
        $statement = $this->database->prepare($sql);
        $statement->execute($values);
        return $statement;
    }
}
