<?php

namespace App\Types;

use App\DB\Database;
use App\Types\TypesRegistry;

use GraphQL\Type\Definition\ObjectType;

class QueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'fields' => function() {
                return [
                    'user' => [
                        'type' => TypesRegistry::user(),
                        'description' => 'Возвращает пользователя по id',
                        'args' => [
                            'id' => TypesRegistry::int()
                        ],
                        'resolve' => function ($root, $args) {
                            return Database::selectOne("SELECT * from user WHERE id = {$args['id']}");
                        }
                    ],
                    'allUsers' => [
                        'type' => TypesRegistry::listOf(TypesRegistry::user()),
                        'description' => 'Список пользователей',
                        'resolve' => function () {
                            return Database::select('SELECT * from user');
                        }
                    ]
                ];
            }
        ];
        parent::__construct($config);
    }
}