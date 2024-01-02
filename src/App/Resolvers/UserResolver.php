<?php

namespace App\Resolvers;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\DB\Database;
use App\utils\Token;

class UserResolver
{
    public static function getUserById($userId)
    {
        return Database::selectOne("SELECT * from user WHERE id = (?)", [$userId]);
    }

    public static function getAllUsers()
    {
        return Database::select('SELECT * from user');
    }

    public static function getUserRoles($userId)
    {
        $rolesData = Database::select("SELECT ur.role_id, r.role_name FROM user u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.id = (?)", [$userId]);
        $roles = array_map(function ($roleData) {
            return $roleData->role_name;
        }, $rolesData);
        return $roles;
    }

    public static function loginUser($username, $password)
    {
        $query = "SELECT user.*, accountconfirm.salt
                FROM user
                LEFT JOIN accountconfirm ON user.id = accountconfirm.user_id
                WHERE user.username = ?";
        
        $user_data = Database::selectOne($query, [$username]);

        if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];

        $salt = $user_data->salt;
        $hashed_password = hash('sha256', $salt.$password);
        $result = $user_data->password === $hashed_password;

        if(!$result) return ["user" => null, "success"=> $result, "message"=> 'Invalid credentials'];

        $AT = Token::generateJWToken($user_data->id);
        $RT = Token::generateJWToken($user_data->id, 'refresh');
        $RT_exp = Token::getPayload($RT)->exp;

        Database::update(
            'tokens',
            ['token_value' => $RT, 'expires_at' => $RT_exp],
            ['user_id' => $user_data->id],
            ['user_id' => $user_data->id],
        );    

        setcookie('NDVR-RT', $RT, time() + 3600 * 24 * 30, "/", "", false, true);
        return ["success"=> $result, "user" => $user_data, "token" => $AT, "message"=> 'Successful account login'];

    }

    public static function logoutUser()
    {
        if (!isset($_COOKIE['NDVR-RT'])) return ["success"=> false, "message"=> 'NDVR-RT has not been received'];
        $token = $_COOKIE['NDVR-RT'];
        $is_token_valid = Token::isTokenValid($token);
        if (!$is_token_valid) return ["success"=> false, "message"=> 'Invalid NDVR-RT'];

        $payload = Token::getPayload($token);
        $result= Database::update(
            'tokens',
            ['token_value' => 'none', 'expires_at' => time() - 3600 * 24 * 30],
            ['user_id' => $payload->user_id],
            ['user_id' => $payload->user_id],
        );

        if (!$result) return ["success"=> false];

        setcookie('NDVR-RT', '', time() - 3600 * 24 * 30, "/", "", false, true);
        return ["success"=> $result, "message"=> 'Successful account logout'];
    }

    public static function authUser()
    {
        if (!isset($_COOKIE['NDVR-RT'])) return ["success"=> false, "message"=> 'NDVR-RT has not been received'];
        $refresh_token = $_COOKIE['NDVR-RT'];
        $is_refresh_valid = Token::isTokenValid($refresh_token);
        if(!$is_refresh_valid) return ["success"=> false, "message"=> 'Invalid NDVR-RT'];
        $user_id = Token::getPayload($refresh_token)->user_id;

        $query = "SELECT * FROM user WHERE id = ?";

        $user_data = Database::selectOne($query, [$user_id]);
        if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];

        $access_token = Token::getBearerToken();

        $is_access_valid = Token::isTokenValid($access_token);
        if($is_access_valid) return ["success"=> true, "user" => $user_data, "message"=> 'Successful authorization'];

        $new_access_token = Token::generateJWToken($user_data->id);
        return ["success"=> true, "user" => $user_data, "token" => $new_access_token, "message"=> 'Successful authorization via NDVR-RT'];
    }
}