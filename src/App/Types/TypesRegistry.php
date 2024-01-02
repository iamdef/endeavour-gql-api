<?php
namespace App\Types;

use GraphQL\Type\Definition\Type;

use App\Types\QueryType;
use App\Types\MutationType;
use App\Types\UserType;
use App\Types\UserMutationTypes\LoginUserResponseType;
use App\Types\UserMutationTypes\LogoutUserResponseType;
use App\Types\UserMutationTypes\AuthUserResponseType;

class TypesRegistry {

    private static $query;
    private static $mutation;
    private static $user;

    public function path() {
        return __CLASS__ . ':' . __FILE__;
    }

    public static function query() {
        return self::$query ?: (self::$query = new QueryType());
    }

    public static function mutation() {
        return self::$mutation ?: (self::$mutation = new MutationType());
    }

    public static function string() {
        return Type::string();
    }

    public static function int() {
        return Type::int();
    }

    public static function id() {
        return Type::id();
    }

    public static function listOf($type) {
        return Type::listOf($type);
    }

    public static function boolean() {
        return Type::boolean();
    }

    public static function nonull() {
        return Type::nonNull();
    }

    // custom types

    public static function user() {
        return self::$user ?: (self::$user = new UserType());
    }    

    public static function loginUserResponse() {
        return new LoginUserResponseType;
    }
    
    public static function logoutUserResponse() {
        return new LogoutUserResponseType;
    }  

    public static function authUserResponse() {
        return new AuthUserResponseType;
    }  

}