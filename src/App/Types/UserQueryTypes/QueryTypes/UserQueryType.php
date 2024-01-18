<?php

namespace App\Types\UserQueryTypes\QueryTypes;

use GraphQL\Type\Definition\InputObjectType;
use App\Types\TypesRegistry;

class UserQueryType extends InputObjectType {
    public function __construct() {
        $config = [
            'name' => 'UserQuery',
            'description' => 'The type of object received during query for user data',
            'fields' => function() {
                return [
                    'username' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Username'
                    ],
                    'id' => [
                        'type' => TypesRegistry::id(),
                        'description' => 'User id'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}