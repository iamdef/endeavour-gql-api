<?php

namespace App\Resolvers;

use App\DB\Database;

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

}