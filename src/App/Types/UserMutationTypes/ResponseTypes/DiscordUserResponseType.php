<?php

namespace App\Types\UserMutationTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class DiscordUserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned when logging in to the account via Discord',
            'fields' => function() {
                return [
                    'user' => [
                        'type' => TypesRegistry::user(),
                        'description' => 'User Data'
                    ],
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Login status'
                    ],
                    'token' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Access Token'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Login status message'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
