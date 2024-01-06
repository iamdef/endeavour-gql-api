<?php

namespace App\Types\UserQueryTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class UserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during user data query',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'User data query status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'User data query status message'
                    ],
                    'user' => [
                        'type' => TypesRegistry::user(),
                        'description' => 'User data'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
