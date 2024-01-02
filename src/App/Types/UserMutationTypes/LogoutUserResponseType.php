<?php

namespace App\Types\UserMutationTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class LogoutUserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'Тип объекта, возвращаемого при выходе из учётной записи',
            'fields' => function() {
                return [
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Статус выхода'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Сообщение о статусе выхода'
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}