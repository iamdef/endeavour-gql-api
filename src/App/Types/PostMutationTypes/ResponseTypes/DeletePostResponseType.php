<?php

namespace App\Types\PostMutationTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;

class DeletePostResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during deleting post',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Deleting post status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Deleting post status message'
                    ],
                    'ids' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::id()),
                        'description' => 'Post ids'
                    ],

                ];
            }
        ];
        parent::__construct($config);
    }
}