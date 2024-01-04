<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;

use App\DB\Database;
use App\Types\TypesRegistry;
use App\Resolvers\UserResolver;



class MutationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'fields' => function() {
                return [
                    'loginUser' => [
                        'type' => TypesRegistry::loginUserResponse(),
                        'description' => 'Logs in the account',
                        'args' => [
                            'username' => TypesRegistry::string(),
                            'password' => TypesRegistry::string()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::loginUser($args['username'], $args['password']);
                        }
                    ],
                    'logoutUser' => [
                        'type' => TypesRegistry::logoutUserResponse(),
                        'description' => 'Logs out the account',
                        'resolve' => function ($root, $args) {
                            return UserResolver::logoutUser();
                        }
                    ],
                    'authUser' => [
                        'type' => TypesRegistry::authUserResponse(),
                        'description' => 'Authorizes the user',
                        'resolve' => function ($root, $args) {
                            return UserResolver::authUser();
                        }
                    ],
                    'registerUser' => [
                        'type' => TypesRegistry::registerUserResponse(),
                        'description' => 'Registers the user',
                        'args' => [
                            'data' => TypesRegistry::inputRegister()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::registerUser($args['data']);
                        }
                    ],
                    'activateUser' => [
                        'type' => TypesRegistry::activateUserResponse(),
                        'description' => 'Activates the user',
                        'args' => [
                            'token' => TypesRegistry::string(),
                            'code' => TypesRegistry::string()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::activateUser($args['token'], $args['code']);
                        }
                    ],
                    'deleteUser' => [
                        'type' => TypesRegistry::deleteUserResponse(),
                        'description' => 'Registers the user',
                        'args' => [
                            'email' => TypesRegistry::string()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::deleteUser($args['email']);
                        }
                    ],
                    'resetPassword' => [
                        'type' => TypesRegistry::resetPasswordResponse(),
                        'description' => 'Sends an email with a link to reset a password',
                        'args' => [
                            'username' => TypesRegistry::string(),
                            'email' => TypesRegistry::string()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::resetPassword($args['username'], $args['email']);
                        }
                    ],
                    'changePassword' => [
                        'type' => TypesRegistry::changePasswordResponse(),
                        'description' => 'Changes user password',
                        'args' => [
                            'data' => TypesRegistry::inputChangePassword()
                        ],
                        'resolve' => function ($root, $args) {
                            return UserResolver::changePassword($args['data']);
                        }
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}