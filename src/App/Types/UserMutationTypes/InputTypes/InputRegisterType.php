<?php

namespace App\Types\UserMutationTypes\InputTypes;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

class InputRegisterType extends InputObjectType {
    public function __construct() {
        $config = [
            'name' => 'InputRegister',
            'description' => 'The type of object received during registration',
            'fields' => function() {
                return [
                    'username' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'Username'
                    ],
                    'email' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'User email'
                    ],
                    'password' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'User password'
                    ],
                    'confirmPassword' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'User password'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}