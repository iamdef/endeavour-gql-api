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
                        'description' => 'Author username and avatar'
                    ],
                    'theme' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Post theme'
                    ],
                    'title' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Post title'
                    ],
                    'blocks' => [
                        'type' => TypesRegistry::jsonScalar(),
                        'description' => 'Blocks of the post content generated by EditorJS'
                    ],
                    'content' => [
                        'type' => TypesRegistry::jsonScalar(),
                        'description' => 'Post content'
                    ],
                    'date' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Date of creation/editing of the post'
                    ],
                    'time' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Time of creation/editing of the post'
                    ],
                    'timestamp' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Timestamp'
                    ],
                    'images' => [
                        'type' => TypesRegistry::jsonScalar(),
                        'description' => 'Array of all post images'
                    ],
                    'media_em' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'The embed link (youtube etc.) for the post preview'
                    ],
                    'media_im' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'The preview image'
                    ],
                    'caption' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Caption for preview image/video of the post'
                    ],
                    'paragraph' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'The post preview paragraph'
                    ],
                    'views' => [
                        'type' => TypesRegistry::int(),
                        'description' => 'Count of views'
                    ],
                    'status' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Draft or publicated'
                    ],
                    'mentioned' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::user()),
                        'description' => 'Mentioned users'
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}

