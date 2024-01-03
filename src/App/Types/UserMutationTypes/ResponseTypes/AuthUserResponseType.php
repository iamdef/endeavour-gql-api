<?php

namespace App\Types\UserMutationTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class AuthUserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during authorization via tokens',
            'fields' => function() {
                return [
                    'user' => [
                        'type' => TypesRegistry::user(),
                        'description' => 'User data'
                    ],
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Authorization status'
                    ],
                    'token' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Access token'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Authorization status message'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
