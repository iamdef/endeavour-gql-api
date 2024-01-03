<?php

namespace App\Types\UserMutationTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class DeleteUserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during deleting user',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Deleting status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Deleting status message'
                    ],
                    'email' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'User email'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
