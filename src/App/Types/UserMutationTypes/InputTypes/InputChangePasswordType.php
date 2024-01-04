<?php

namespace App\Types\UserMutationTypes\InputTypes;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

class InputChangePasswordType extends InputObjectType {
    public function __construct() {
        $config = [
            'name' => 'InputChangePassword',
            'description' => 'The type of object received during changing password',
            'fields' => function() {
                return [
                    'password' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'New password'
                    ],
                    'confirmPassword' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'Confirmated password'
                    ],
                    'token' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The change password token from email'
                    ],
                    'code' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The change code from email'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}