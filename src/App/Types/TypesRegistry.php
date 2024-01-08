<?php
namespace App\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

use App\Types\QueryType;
use App\Types\MutationType;
use App\Types\UserType;

use App\Types\UserQueryTypes\ResponseTypes\UserResponseType;
use App\Types\UserQueryTypes\ResponseTypes\AllUsersResponseType;

use App\Types\UserMutationTypes\ResponseTypes\LoginUserResponseType;
use App\Types\UserMutationTypes\ResponseTypes\DiscordUserResponseType;
use App\Types\UserMutationTypes\ResponseTypes\LogoutUserResponseType;
use App\Types\UserMutationTypes\ResponseTypes\AuthUserResponseType;
use App\Types\UserMutationTypes\ResponseTypes\RegisterUserResponseType;
use App\Types\UserMutationTypes\ResponseTypes\DeleteUserResponseType;
use App\Types\UserMutationTypes\ResponseTypes\ActivateUserResponseType;
use App\Types\UserMutationTypes\ResponseTypes\ResetPasswordResponseType;
use App\Types\UserMutationTypes\ResponseTypes\ChangePasswordResponseType;

use App\Types\UserMutationTypes\InputTypes\InputRegisterType;
use App\Types\UserMutationTypes\InputTypes\InputChangePasswordType;

class TypesRegistry {

    private static $query;
    private static $mutation;
    private static $user;

    public function path() {
        return __CLASS__ . ':' . __FILE__;
    }

    public static function query() {
        return self::$query ?: (self::$query = new QueryType());
    }

    public static function mutation() {
        return self::$mutation ?: (self::$mutation = new MutationType());
    }

    public static function string() {
        return Type::string();
    }

    public static function int() {
        return Type::int();
    }

    public static function id() {
        return Type::id();
    }

    public static function listOf($type) {
        return Type::listOf($type);
    }

    public static function boolean() {
        return Type::boolean();
    }

    public static function inputRegister() {
        return new InputRegisterType;
    }

    public static function inputChangePassword() {
        return new InputChangePasswordType;
    }

    // custom response types

    public static function user() {
        return self::$user ?: (self::$user = new UserType());
    }    

    public static function loginUserResponse() {
        return new LoginUserResponseType;
    }

    public static function discordUserResponse() {
        return new DiscordUserResponseType;
    }
    
    public static function logoutUserResponse() {
        return new LogoutUserResponseType;
    }  

    public static function authUserResponse() {
        return new AuthUserResponseType;
    }  

    public static function registerUserResponse() {
        return new RegisterUserResponseType;
    }  

    public static function deleteUserResponse() {
        return new DeleteUserResponseType;
    }  
    
    public static function activateUserResponse() {
        return new ActivateUserResponseType;
    }  

    public static function resetPasswordResponse() {
        return new ResetPasswordResponseType;
    }  

    public static function changePasswordResponse() {
        return new ChangePasswordResponseType;
    }

    public static function userResponse() {
        return new UserResponseType;
    }

    public static function allUsersResponse() {
        return new AllUsersResponseType;
    }
}