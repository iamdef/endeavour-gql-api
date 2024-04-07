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
                    'total' => [
                        'type' => TypesRegistry::int(),
                        'description' => 'Total posts'
                    ],
                    'posts' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::post()),
                        'description' => 'Posts'
                    ],
                    'prepared' => [
                        'type' => TypesRegistry::jsonScalar(),
                        'description' => 'Post data'
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}
