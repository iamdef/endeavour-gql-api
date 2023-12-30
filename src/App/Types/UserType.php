<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\DB\Database;
use GraphQL\Type\Definition\ObjectType;
use App\Types\TypesRegistry;


class UserType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'Пользователь',
            'fields' => function() {
                return [
                    'id' => [
                        'type' => TypesRegistry::id(),
                        'description' => 'Идентификатор пользователя'
                    ],
                    'username' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Имя пользователя'
                    ],
                    'email' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'E-mail пользователя'
                    ],
                    'avatar' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Аватар пользователя'
                    ],
                    'roles' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::string()),
                        'description' => 'Роли пользователя',
                        'resolve' => function ($root) {
                            $rolesData = Database::select("SELECT ur.role_id, r.role_name FROM user u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.id = (?)", [$root->id]);
                            $roles = array_map(function ($roleData) {
                                return $roleData->role_name;
                            }, $rolesData);
                            return $roles;
                        }
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}

