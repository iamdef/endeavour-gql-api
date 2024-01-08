<?php
namespace App\DB;

use PDO;
use \PDOException;
use App\utils\Logme;

class Database {

    private static $pdo;

    public static function init($config) {
        try {
            self::$pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']}", $config['username'], $config['password']);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            Logme::critical('Database connection error', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ]);
            throw new \Exception("Database connection error: " . $e->getMessage());
        }
    }

    public static function prepare($query) {
        try {
            return self::$pdo->prepare($query);
        } catch (\PDOException $e) {
            Logme::error('Error preparing database query', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ]);
            throw new \Exception("Error preparing database query: " . $e->getMessage());
        }
    }


    public static function selectOne($query, $params = []) {
        $records = self::select($query, $params);
        return $records ? array_shift($records) : null;
    }

    public static function select($query, $params = []) {
        try {
            $statement = self::prepare($query);
            $statement->execute($params);
            return $statement->fetchAll();
        } catch (PDOException $e) {
            Logme::error("Error executing query 'select'", [
                'message' => $e->getMessage(),
                'query' => $query,
                'params' => $params,
                'time' => date('Y-m-d H:i:s')
            ]);
            throw new \Exception("Error executing query 'select': " . $e->getMessage());
        }
    }

    public static function insert($table, $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));

            $query = "INSERT INTO $table ($columns) VALUES ($values)";
            
            $statement = self::prepare($query);
            $statement->execute($data);

            return self::$pdo->lastInsertId();
        } catch (PDOException $e) {
            Logme::error("Error executing query 'insert'", [
                'message' => $e->getMessage(),
                'table' => $table,
                'data' => $data,
                'time' => date('Y-m-d H:i:s')
            ]);
            throw new \Exception("Error executing query 'insert': " . $e->getMessage());
        }
    }

    public static function update($table, $data, $whereClause, $whereParams) {
        try {

            // Forming the SET part of the request
            $setClause = implode(', ', array_map(function($key) {
                return "$key = :$key";
            }, array_keys($data)));

            // Forming the WHERE part of the request
            $whereConditions = implode(' AND ', array_map(function($key) {
                return "$key = :$key";
            }, array_keys($whereClause)));

            $query = "UPDATE $table SET $setClause WHERE $whereConditions";

            // Combining the parameters for SET and WHERE
            $params = array_merge($data, $whereParams);

            $statement = self::prepare($query);
            $statement->execute($params);

            return $statement->rowCount() !== 0;

        } catch (PDOException $e) {
            Logme::error("Error executing query 'update'", [
                'message' => $e->getMessage(),
                'table' => $table,
                'data' => $data,
                'clause' => $whereClause,
                'params' => $whereParams,
                'time' => date('Y-m-d H:i:s')
            ]);
            throw new \Exception("Error executing query 'update': " . $e->getMessage());
        }
    }

    public static function delete($table, $whereClause, $whereParams) {
        try {
            
            // Forming the WHERE part of the request
            $whereConditions = implode(' AND ', array_map(function($key) {
                return "$key = :$key";
            }, array_keys($whereClause)));

            $query = "DELETE FROM $table WHERE $whereConditions";

            $statement = self::prepare($query);
            $statement->execute($whereParams);

            return $statement->rowCount() !== 0;
        } catch (PDOException $e) {
            Logme::error("Error executing query 'delete'", [
                'message' => $e->getMessage(),
                'table' => $table,
                'clause' => $whereClause,
                'params' => $whereParams,
                'time' => date('Y-m-d H:i:s')
            ]);
            throw new \Exception("Error executing query 'delete': " . $e->getMessage());
        }
    }

}