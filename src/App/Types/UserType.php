<?php

namespace App\Types;

use App\DB\Database;
use App\Types;
use GraphQL\Type\Definition\ObjectType;
use App\Types\TypesRegistry;

class UserType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'Пользователь',
            'fields' => function() {
                return [
                    'id' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Идентификатор пользователя'
                    ],
                    'username' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Имя пользователя'
                    ],
                    'email' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'E-mail пользователя'
                    ],
                    'avatar' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Аватар пользователя'
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}