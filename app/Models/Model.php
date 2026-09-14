<?php

namespace app\Models;

use Exception;

abstract class Model
{
    protected \PDO $db;

    public function __construct()
    {
        try {
            /*Singleton
            Utiliza el metodo getConnection() de database para guardarlo en $db.
            */
            $this->db = \Database::getConnection();
        } catch (Exception $e) {
            throw new Exception("Error al conectar a la base de datos: " . $e->getMessage());
        }
    }
}
