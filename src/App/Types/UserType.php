<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\DB\Database;
use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


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
                    'about' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Описание пользователя'
                    ],
                    'roles' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::string()),
                        'description' => 'Роли пользователя',
                        'resolve' => function ($root) {
                            return UserResolver::getUserRoles($root->id);
                        }
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}

