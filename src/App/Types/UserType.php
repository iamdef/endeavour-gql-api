<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use GraphQL\Type\Definition\Type;
use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class UserType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'User',
            'fields' => function() {
                return [
                    'id' => [
                        'type' => TypesRegistry::id(),
                        'description' => 'User id'
                    ],
                    'username' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Username'
                    ],
                    'email' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'User e-mail'
                    ],
                    'avatar' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'User avatar'
                    ],
                    'about' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'User description'
                    ],
                    'roles' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::string()),
                        'description' => 'User roles',
                        'resolve' => function ($root) {
                            return UserResolver::getUserRoles($root->id);
                        }
                    ],
                    'created_date' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Account created date'
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}

