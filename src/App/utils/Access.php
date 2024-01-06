<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../vendor/autoload.php';
use Dotenv\Dotenv;
use App\utils\Token;
use App\DB\Database;
use App\Resolvers\UserResolver;


$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

class Access {

    public static function check($scope)
    {
        $user_id = self::getIdFromToken();
        if (!$user_id) return false;
        $roles = self::getUserRoles($user_id);
        $result = array_intersect($scope, $roles);
        return count($result) > 0;
    }

    public static function getIdFromToken()
    {
        $auth = UserResolver::authUser();
        if (!$auth['success']) return false;
        $token = $auth['token'];

        $user_id = Token::getPayload($token)->user_id;
        return $user_id;
    }

    public static function getUserRoles($userId)
    {
        $rolesData = Database::select("SELECT ur.role_id, r.role_name FROM user u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.id = (?)", [$userId]);
        $roles = array_map(function ($roleData) {
            return $roleData->role_name;
        }, $rolesData);
        return $roles;
    }


}