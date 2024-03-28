<?php

namespace App\Types\PostMutationTypes\ResponseTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;

class SavePostResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'The type of object returned during saving post',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Saving status'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Saving status message'
                    ],
                    'id' => [
                        'type' => TypesRegistry::id(),
                        'description' => 'Post id'
                    ],
                    // 'post' => [
                    //     'type' => TypesRegistry::jsonScalar(),
                    //     'description' => 'Post id'
                    // ]
                    'post' => [
                        'type' => TypesRegistry::post(),
                        'description' => 'Post id'
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}
