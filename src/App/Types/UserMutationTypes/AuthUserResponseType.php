<?php

namespace App\Types\UserMutationTypes;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class AuthUserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'Тип объекта, возвращаемого при авторизации через токены',
            'fields' => function() {
                return [
                    'user' => [
                        'type' => TypesRegistry::user(),
                        'description' => 'Данные пользователя'
                    ],
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Статус авторизации'
                    ],
                    'token' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Токен доступа'
                    ],
                    'message' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Сообщение о статусе авторизации'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}
