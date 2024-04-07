<?php

namespace App\Types\PostQueryTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;

class PostResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during one post query',
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
                    'post' => [
                        'type' => TypesRegistry::post(),
                        'description' => 'Post data'
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
