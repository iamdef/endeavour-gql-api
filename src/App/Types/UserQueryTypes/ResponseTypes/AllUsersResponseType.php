<?php

namespace App\Types\UserQueryTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;

class AllUsersResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during all users data query',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Users data query status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Users data query status message'
                    ],
                    'users' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::user()),
                        'description' => 'Users data'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
