<?php

namespace App\Types\UserMutationTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class RegisterUserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during registration',
            'fields' => function() {
                return [
                    'email' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'User email'
                    ],
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Registration status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Registration status message'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
