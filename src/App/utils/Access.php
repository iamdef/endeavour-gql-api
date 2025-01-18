<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// require_once 'Vendor/autoload.php';
use Dotenv\Dotenv;
use App\utils\Token;
use App\DB\Database;
use App\Resolvers\UserResolver;
use App\utils\Logme;


$dotenv = Dotenv::createImmutable('Vendor/' . '../');
$dotenv->load();

class Access {

    public static function check($scope)
    {
        $authorize_res = self::authorize($scope);
        if (!$authorize_res['authorize']) {
            Logme::info('Unauthorized access attempt', [
                'username' => $authorize_res['username'],
                'time' => date('Y-m-d H:i:s'),
                'path' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['function']
            ]);
            return false;
        }
        return true;
    }

    public static function authorize($scope)
    {
        $user_id = self::getIdFromToken();
        if (!$user_id) return ['authorize' => false, 'username' => null];
        $username = self::getUserRoles($user_id)['username'];
        $roles = self::getUserRoles($user_id)['roles'];
        $result = array_intersect($scope, $roles);
        $is_authorized = count($result) > 0;
        return ['authorize' => $is_authorized, 'username' => $username];
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
        try {
            $rolesData = Database::select("SELECT
            u.username, ur.role_id, r.role_name
            FROM user u
            JOIN user_roles ur
            ON u.id = ur.user_id
            JOIN roles r
            ON ur.role_id = r.role_id
            WHERE u.id = (?)", [$userId]);

            if (empty($rolesData)) return ['username' => null, 'roles' => []];

            $roles = array_map(function ($roleData) {
                return $roleData->role_name;
            }, $rolesData);

            return ['username' => $rolesData[0]->username, 'roles' => $roles];

        } catch (\Exception $e) {
            Logme::warning('Error fetching user roles by id', [
                'message' => $e->getMessage(),
                'id' => $userId
            ]);
            return ['username' => null, 'roles' => []];
        }
    }


}