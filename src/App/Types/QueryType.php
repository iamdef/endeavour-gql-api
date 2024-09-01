<?php

namespace App\Types;

use GraphQL\Type\Definition\ObjectType;

use App\DB\Database;
use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;
use App\Resolvers\PostResolver;



class QueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'fields' => function() {
                return [
                    'getUser' => [
                        'type' => TypesRegistry::userResponse(),
                        'description' => 'Возвращает пользователя по id',
                        'args' => [
                            'data' => TypesRegistry::userQuery()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::getUser($args['data']);
                        }
                    ],
                    'getAllUsers' => [
                        'type' => TypesRegistry::allUsersResponse(),
                        'description' => 'Список пользователей',
                        'resolve' => function () {
                            return UserResolver::getAllUsers();
                        }
                    ],
                    'getAllPosts' => [
                        'type' => TypesRegistry::getAllPostsResponse(),
                        'description' => 'Fetching posts',
                        'args' => [
                            'cursor' => TypesRegistry::int(),
                            'limit' => TypesRegistry::int(),
                            'sortDirection' => TypesRegistry::string(),
                            'theme' => TypesRegistry::string(),
                            'status' => TypesRegistry::string(),
                            'person' => TypesRegistry::string(),
                        ],
                        'resolve' => function ($root, $args) {
                            return PostResolver::getAllPosts($args['cursor'], $args['limit'], $args['sortDirection'], $args['theme'], $args['status'], $args['person']);
                        }
                    ],
                    'getPost' => [
                        'type' => TypesRegistry::getPostResponse(),
                        'description' => 'Fetching the post',
                        'args' => [
                            'id' => TypesRegistry::int(),
                        ],
                        'resolve' => function ($root, $args) {
                            return PostResolver::getPost($args['id']);
                        }
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}