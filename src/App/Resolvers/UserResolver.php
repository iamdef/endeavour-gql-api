<?php

namespace App\Resolvers;

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

        if (!$user_data) return ["user" => null, "success"=> false];

        $salt = $user_data->salt;
        $hashed_password = hash('sha256', $salt.$password);
        $result = $user_data->password === $hashed_password;

        if(!$result) return ["user" => null, "success"=> $result];

        $AT = Token::generateJWToken($user_data->id);

        return ["success"=> $result, "user" => $user_data, "token" => $AT];

    }
}