<?php
namespace App\DB;

use PDO;

class Database {

    private static $pdo;

    public static function init($config) {
        try {
            self::$pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']}", $config['username'], $config['password']);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die();
        }
    }

    public static function selectOne($query, $params = []) {
        $records = self::select($query, $params);
        return array_shift($records);
    }
    
    public static function select($query, $params = []) {

        try {
            $statement = self::$pdo->prepare($query);
            $statement->execute($params);
            return $statement->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die();
        }
    }

}