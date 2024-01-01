<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\DB\Database;
use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;


class LoginUserResponseType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'Тип объекта, возвращаемого при входе в приложение',
            'fields' => function() {
                return [
                    'user' => [
                        'type' => TypesRegistry::user(),
                        'description' => 'Данные пользователя'
                    ],
                    'success' => [
                        'type' => TypesRegistry::boolean(),
                        'description' => 'Статус входа'
                    ],
                    'token' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Токен доступа'
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}
