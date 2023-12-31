<?php

namespace App\Types;

use GraphQL\Type\Definition\ObjectType;

use App\DB\Database;
use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;



class QueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'fields' => function() {
                return [
                    'getUser' => [
                        'type' => TypesRegistry::userResponse(),
                        'description' => 'Возвращает пользователя по id',
                        'args' => [
                            'id' => TypesRegistry::id()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::getUserById($args['id']);
                        }
                    ],
                    'getAllUsers' => [
                        'type' => TypesRegistry::allUsersResponse(),
                        'description' => 'Список пользователей',
                        'resolve' => function () {
                            return UserResolver::getAllUsers();
                        }
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}