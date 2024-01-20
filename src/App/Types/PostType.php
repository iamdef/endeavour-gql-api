<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;
use App\Types\TypesRegistry;


class PostType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'Post',
            'fields' => function() {
                return [
                    'id' => [
                        'type' => TypesRegistry::id(),
                        'description' => 'Post id'
                    ],
                    'author' => [
                        'type' => TypesRegistry::user(),
                        'description' => 'Author username'
                    ],
                    'theme' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Post theme'
                    ],
                    'content' => [
                        'type' => TypesRegistry::jsonScalar(),
                        'description' => 'Post content'
                    ],
                    'views' => [
                        'type' => TypesRegistry::int(),
                        'description' => 'Count of views'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}

