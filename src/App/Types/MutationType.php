<?php

namespace App\Types;

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
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}