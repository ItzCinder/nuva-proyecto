<?php

class Database
{
    private static ?PDO $instance = null;

    /*
    Prohibir hacer contruct y clone en la clase DataBase
    Singleton: Hace que solo se pueda instanciar una vez la clase en todo el codigo.
    */
    private function __construct()
    {
    }
    private function __clone()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = (int) ($_ENV['DB_PORT'] ?? 3306);
            $database = $_ENV['DB_NAME'] ?? '';
            $username = $_ENV['DB_USER'] ?? '';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$instance = new PDO($dsn, $username, $password, $options);
        }

        return self::$instance;
    }
}
