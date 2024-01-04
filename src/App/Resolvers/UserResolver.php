<?php

namespace App\Resolvers;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\DB\Database;
use App\utils\Token;
use App\utils\Email;

class UserResolver
{
    public static function getUserById($userId)
    {
        return Database::selectOne("SELECT * from user WHERE id = (?)", [$userId]);
    }

    public static function getAllUsers()
    {
        return Database::select('SELECT * from user');
    }

    public static function getUserRoles($userId)
    {
        $rolesData = Database::select("SELECT ur.role_id, r.role_name FROM user u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE u.id = (?)", [$userId]);
        $roles = array_map(function ($roleData) {
            return $roleData->role_name;
        }, $rolesData);
        return $roles;
    }

    public static function loginUser($username, $password)
    {
        $is_username_valid = preg_match('/^[a-zA-Z0-9_]+$/', $username) && preg_match('/^.{3,}$/', $username);
        $is_password_valid = preg_match('/^.{6,}$/', $password);


        $query = "SELECT user.*, registerconfirm.salt
                FROM user
                LEFT JOIN registerconfirm ON user.id = registerconfirm.user_id
                WHERE user.username = ? AND user.status = 1";
        
        $user_data = Database::selectOne($query, [$username]);

        if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];

        $salt = $user_data->salt;
        $hashed_password = hash('sha256', $salt.$password);
        $result = $user_data->password === $hashed_password;

        if(!$result) return ["user" => null, "success"=> $result, "message"=> 'Invalid credentials'];

        $AT = Token::generateJWToken($user_data->id);
        $RT = Token::generateJWToken($user_data->id, 'refresh');
        $RT_exp = Token::getPayload($RT)->exp;

        Database::update(
            'tokens',
            ['token_value' => $RT, 'expires_at' => $RT_exp],
            ['user_id' => $user_data->id],
            ['user_id' => $user_data->id],
        );    

        setcookie('NDVR-RT', $RT, time() + 3600 * 24 * 30, "/", "", false, true);
        return ["success"=> $result, "user" => $user_data, "token" => $AT, "message"=> 'Successful account login'];

    }

    public static function logoutUser()
    {
        if (!isset($_COOKIE['NDVR-RT'])) return ["success"=> false, "message"=> 'NDVR-RT has not been received'];
        $token = $_COOKIE['NDVR-RT'];
        $is_token_valid = Token::isTokenValid($token);
        if (!$is_token_valid) return ["success"=> false, "message"=> 'Invalid NDVR-RT'];

        $payload = Token::getPayload($token);
        $result= Database::update(
            'tokens',
            ['token_value' => 'none', 'expires_at' => time() - 3600 * 24 * 30],
            ['user_id' => $payload->user_id],
            ['user_id' => $payload->user_id],
        );

        if (!$result) return ["success"=> false];

        setcookie('NDVR-RT', '', time() - 3600 * 24 * 30, "/", "", false, true);
        return ["success"=> $result, "message"=> 'Successful account logout'];
    }

    public static function authUser()
    {
        if (!isset($_COOKIE['NDVR-RT'])) return ["success"=> false, "message"=> 'NDVR-RT has not been received'];
        $refresh_token = $_COOKIE['NDVR-RT'];
        $is_refresh_valid = Token::isTokenValid($refresh_token);
        if(!$is_refresh_valid) return ["success"=> false, "message"=> 'Invalid NDVR-RT'];
        $user_id = Token::getPayload($refresh_token)->user_id;

        $query = "SELECT * FROM user WHERE id = ?";

        $user_data = Database::selectOne($query, [$user_id]);
        if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];

        $access_token = Token::getBearerToken();

        $is_access_valid = Token::isTokenValid($access_token);
        if($is_access_valid) return ["success"=> true, "user" => $user_data, "message"=> 'Successful authorization'];

        $new_access_token = Token::generateJWToken($user_data->id);
        return ["success"=> true, "user" => $user_data, "token" => $new_access_token, "message"=> 'Successful authorization via NDVR-RT'];
    }

    public static function registerUser($data)
    {
        $is_username_valid = preg_match('/^[a-zA-Z0-9_]+$/', $data['username']) && preg_match('/^.{3,}$/', $data['username']);
        $is_email_valid = preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $data['email']);
        $is_password_valid = $data['password'] === $data['confirmPassword'] && preg_match('/^.{6,}$/', $data['password']);

        if (!$is_username_valid || !$is_email_valid || !$is_password_valid) {
            return ["success"=> false, "message"=> 'Invalid credentials'];
        }

        $bytes = random_bytes(8);
        $salt = bin2hex($bytes);

        $prep_data = [
            'username' => $data['username'],
            'password' => hash('sha256', $salt.$data['password']),
            'email' => $data['email'],
            'status' => 0,
            'created_date' => date('Y-m-d H:i:s'),
        ];

        $query_exist = "SELECT * FROM user WHERE username = ? OR email = ?";

        $is_user_exist = Database::selectOne($query_exist, [$prep_data['username'], $prep_data['email']]);
        if ($is_user_exist) {
            return ["success"=> false, "message"=> 'Such a user already exist'];
        }

        $user_id = Database::insert('user', [
            'username' => $prep_data['username'],
            'email' => $prep_data['email'],
            'password' => $prep_data['password'],
            'created_date' => $prep_data['created_date']
        ]);

        if(!$user_id) {
            return ["success"=> false, "message"=> 'Failed registration'];
        }

        $tokens_id = Database::insert('tokens', [
            'user_id' => $user_id
        ]);
        $roles_id = Database::insert('user_roles', [
            'user_id' => $user_id
        ]);

        $code = rand(11111, 99999);
        $code_id = Database::insert('registerconfirm', [
            'user_id' => $user_id,
            'code' => $code,
            'salt' => $salt
        ]);

        if(!$code_id || !$roles_id || $tokens_id) {
            return ["success"=> false, "message"=> 'The data in the database could not be updated'];
        }

        $config = [
            'user_name' => $prep_data['username'],
            'user_email' => $prep_data['email'],
            'type' => 'registration',
            'token' => Token::generateJWToken($user_id, 'register'),
            'code' => $code,
        ];

        if (!Email::send($config)) {
            Database::delete('user', ['user_id' => $user_id], ['user_id' => $user_id]);
            return ["success"=> false, "message"=> 'The email could not be sent', "email" => $prep_data['email']];
        }

        return ["success"=> true, "email" => $prep_data['email'], "message"=> 'Waiting for confirmation via email'];
    }

    public static function activateUser($token, $code)
    {
        if (!Token::isTokenValid($token)) return ["success"=> false, "message"=> 'Invalid token'];
        $user_id = Token::getPayload($token)->user_id;
        if (!$user_id) return ["success"=> false, "message"=> 'Invalid token'];
        $query_code = "SELECT * FROM registerconfirm WHERE user_id = ?";
        $result_code = Database::selectOne($query_code, [$user_id]);
        if (!$result_code) return ["success"=> false, "message"=> 'No such user'];
        if ($result_code->code != $code) return ["success"=> false, "message"=> 'Invalid code'];

        $res_user = Database::update('user', ['status' => 1], ['id' => $user_id], ['id' => $user_id]);
        $res_salt = Database::update('registerconfirm', ['code' => rand(11111, 99999)], ['user_id' => $user_id], ['user_id' => $user_id]);
        if (!$res_user || !$res_salt) return ["success"=> false, "message"=> 'The data in the database could not be updated'];
        return ["success"=> true, "message"=> 'The user has been successfully activated'];

    }

    public static function deleteUser($email)
    {
        $result = Database::delete('user', ['email' => $email, 'status' => 0], ['email' => $email, 'status' => 0]);
        return $result ? ["success"=> true, "message"=> 'The user has been successfully deleted', 'email' => $email] : ["success"=> false, "message"=> 'The user cannot be deleted', 'email' => $email];
    }

    public static function resetPassword($username, $email)
    {
        $is_username_valid = preg_match('/^[a-zA-Z0-9_]+$/', $username) && preg_match('/^.{3,}$/', $username);
        $is_email_valid = preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/',$email);

        if (!$is_username_valid || !$is_email_valid) return ["success"=> false, "message"=> 'Invalid credentials', 'email' => $email];
        
        $user = Database::selectOne("SELECT * from user WHERE username = ? AND email = ?", [$username, $email]);
        if(!$user) return ["success"=> false, "message"=> 'No such user', 'email' => $email];

        $token = Token::generateJWToken($user->id, 'reset');
        $code = Database::selectOne("SELECT * from registerconfirm WHERE user_id = ?", [$user->id])->code;
        $config = [
            'user_name' => $username,
            'user_email' => $email,
            'type' => 'reset',
            'token' => $token,
            'code' => $code,
        ];
        if (!Email::send($config)) return ["success"=> false, "message"=> 'The email could not be sent', 'email' => $email];

        return ["success"=> true, "message"=> 'The email with the link to change the password was successfully sent', 'email' => $email];
    }

    public static function changePassword($data)
    {
        if (!Token::isTokenValid($data['token'])) return ["success"=> false, "message"=> 'Invalid token'];
        
        $user_id = Token::getPayload($data['token'])->user_id;
        if (!$user_id) return ["success"=> false, "message"=> 'Invalid token'];

        $is_password_valid = $data['password'] === $data['confirmPassword'] && preg_match('/^.{6,}$/', $data['password']);
        if (!$is_password_valid) return ["success"=> false, "message"=> 'Invalid credentials'];

        $query_code = "SELECT * FROM registerconfirm WHERE user_id = ?";
        $result_code = Database::selectOne($query_code, [$user_id]);
        if (!$result_code) return ["success"=> false, "message"=> 'No such user'];
        if ($result_code->code != $data['code']) return ["success"=> false, "message"=> 'Invalid code'];

        $bytes = random_bytes(8);
        $salt = bin2hex($bytes);
        $new_password = hash('sha256', $salt.$data['password']);

        $res_user = Database::update('user', ['password' => $new_password], ['id' => $user_id], ['id' => $user_id]);
        $res_salt = Database::update('registerconfirm', ['salt' => $salt, 'code' => rand(11111, 99999)], ['user_id' => $user_id], ['user_id' => $user_id]);
        if (!$res_user || !$res_salt) return ["success"=> false, "message"=> 'The data in the database could not be updated'];
        return ["success"=> true, "message"=> 'The password has been successfully changed'];
    }
}