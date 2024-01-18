<?php

namespace App\Types\PostQueryTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;

class AllPostsResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during all posts query',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Post data query status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Post data query status message'
                    ],
                    'posts' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::jsonScalar()),
                        'description' => 'Post data'
                    ],
                    'ids' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::id()),
                        'description' => 'Posts ids'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
