<?php

namespace App\Types\PostMutationTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;

class ChangePostStatusResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during changing post status',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Changing post status status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Changing post status message'
                    ],
                    'ids' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::id()),
                        'description' => 'Post ids'
                    ],
                    'status' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Posts status'
                    ],
                    'posts' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::post()),
                        'description' => 'Updated posts'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}