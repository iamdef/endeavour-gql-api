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

    public static function insert($table, $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));

            $query = "INSERT INTO $table ($columns) VALUES ($values)";
            
            $statement = self::$pdo->prepare($query);
            $statement->execute($data);

            return self::$pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die();
        }
    }

    public static function update($table, $data, $whereClause, $whereParams) {
        try {

            // Формируем SET часть запроса
            $setClause = implode(', ', array_map(function($key) {
                return "$key = :$key";
            }, array_keys($data)));

            // Формируем WHERE часть запроса
            $whereConditions = implode(' AND ', array_map(function($key) {
                return "$key = :$key";
            }, array_keys($whereClause)));

            $query = "UPDATE $table SET $setClause WHERE $whereConditions";

            // Объединяем параметры для SET и WHERE
            $params = array_merge($data, $whereParams);

            $statement = self::$pdo->prepare($query);
            $result = $statement->execute($params);
            return $result;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die();
        }
    }

}