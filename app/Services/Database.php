<?php

class Database
{
    private static ?PDO $instance = null;

    /*
    Prohibir hacer contruct y clone en la clase DataBase
    Singleton: Hace que solo se pueda instanciar una vez la clase en todo el codigo.
    */
    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host    = 'localhost';
            $db      = 'mi_base_datos';
            $user    = 'root';
            $pass    = '';
            $charset = 'utf8mb4';
            $port    = 3306;

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }
}