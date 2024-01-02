<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\DB\Database;
use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;



class MutationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'fields' => function() {
                return [
                    'loginUser' => [
                        'type' => TypesRegistry::loginUserResponse(),
                        'description' => 'Логинит пользователя',
                        'args' => [
                            'username' => TypesRegistry::string(),
                            'password' => TypesRegistry::string()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::loginUser($args['username'], $args['password']);
                        }
                    ],
                    'logoutUser' => [
                        'type' => TypesRegistry::logoutUserResponse(),
                        'description' => 'Разлогинивает пользователя',
                        'resolve' => function ($root, $args) {
                            return UserResolver::logoutUser();
                        }
                    ],
                    'authUser' => [
                        'type' => TypesRegistry::authUserResponse(),
                        'description' => 'Авторизовывает пользователя',
                        'resolve' => function ($root, $args) {
                            return UserResolver::authUser();
                        }
                    ],

                ];
            }
        ];
        parent::__construct($config);
    }
}