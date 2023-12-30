<?php
namespace App\Types;

use GraphQL\Type\Definition\Type;

use App\Types\QueryType;
use App\Types\UserType;

class TypesRegistry {

    private static $query;
    private static $user;

    public function path() {
        return __CLASS__ . ':' . __FILE__;
    }

    public static function query() {
        return self::$query ?: (self::$query = new QueryType());
    }

    public static function string() {
        return Type::string();
    }

    public static function int() {
        return Type::int();
    }

    public static function listOf($type) {
        return Type::listOf($type);
    }

    public static function user() {
    return self::$user ?: (self::$user = new UserType());
    }
}