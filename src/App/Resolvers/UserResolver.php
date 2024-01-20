<?php

namespace App\Resolvers;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Vendor/autoload.php';
use Dotenv\Dotenv;
use App\DB\Database;
use App\utils\Access;
use App\utils\Token;
use App\utils\Email;
use App\utils\Curl;
use App\utils\Validator;
use App\utils\Logme;

$dotenv = Dotenv::createImmutable('Vendor/' . '../');
$dotenv->load();

class UserResolver
{
    public static function getUser($data)
    {
        try {
            if (isset($data['id'])) {
                $query = "SELECT * FROM user WHERE id = ?";
                $user_data = Database::selectOne($query, [$data['id']]);
            } else {
                $query = "SELECT * FROM user WHERE username = ?";
                $user_data = Database::selectOne($query, [$data['username']]);
            }
            
            if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];
            return ["success"=> true, "message"=> 'Successful query', 'user' => $user_data];

        } catch (\Exception $e) {
            Logme::warning('Error fetching user by id', [
                'message' => $e->getMessage()
            ]);
            return ["success"=> false, "message"=> 'Fetching error', 'user' => null];
        }
    }

    public static function getAllUsers()
    {
        $scope = ['admin', 'player'];
        if(!Access::check($scope)) return ["success"=> false, "message"=> 'Unauthorized'];
        try {
            $users = Database::select('SELECT * FROM user');
            return ["users" => $users, "success"=> true, "message"=> 'Successful query'];

        } catch (\Exception $e) {
            Logme::warning('Error fetching users', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["users" => null, "success"=> false, "message"=> 'Fetching error'];
        }
    }

    public static function getUserRoles($userId)
    {
        try {
            $rolesData = Database::select("SELECT
            ur.role_id, r.role_name
            FROM user u
            JOIN user_roles ur
            ON u.id = ur.user_id
            JOIN roles r
            ON ur.role_id = r.role_id
            WHERE u.id = (?)", [$userId]);

            if (empty($rolesData)) return [];

            $roles = array_map(function ($roleData) {
                return $roleData->role_name;
            }, $rolesData);

            return $roles;

        } catch (\Exception $e) {
            Logme::warning('Error fetching user roles by id', [
                'message' => $e->getMessage(),
                'id' => $userId,
                'time' => date('Y-m-d H:i:s')
            ]);
            return [];
        }
    }

    public static function loginUser($username, $password)
    {
        $is_username_valid = Validator::username($username);
        $is_password_valid = Validator::password($password);

        if (!$is_username_valid || !$is_password_valid) return ["user" => null, "success"=> false, "message"=> 'Invalid credentials'];

        $query = "SELECT user.*, registerconfirm.salt
                FROM user
                LEFT JOIN registerconfirm ON user.id = registerconfirm.user_id
                WHERE user.username = ? AND user.status = 1";
        
        try {
            $user_data = Database::selectOne($query, [$username]);

            if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];

            $salt = $user_data->salt;
            $hashed_password = hash('sha256', $salt.$password);
            $result = $user_data->password === $hashed_password;
    
            if(!$result) return ["user" => null, "success"=> false, "message"=> 'Invalid password'];
    
            $AT = Token::generateJWToken($user_data->id);
            $RT = Token::generateJWToken($user_data->id, 'refresh');
            $RT_exp = Token::getPayload($RT)->exp;
    
            Database::update(
                'tokens',
                ['token_value' => $RT, 'expires_at' => $RT_exp],
                ['user_id' => $user_data->id],
                ['user_id' => $user_data->id],
            );    
            
            setcookie('NDVR-GT', '', time() - 3600 * 24 * 30, "/", "", false, true);
            setcookie('NDVR-RT', $RT, time() + 3600 * 24 * 30, "/", "", false, true);
            return ["success"=> $result, "user" => $user_data, "token" => $AT, "message"=> 'Successful account login'];

        } catch (\Exception $e) {
            Logme::warning('Error logining user', [
                'message' => $e->getMessage(),
                'username' => $username,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["success"=> false, "user" => null, "token" => null, "message"=> 'Error logining user'];
        }
    }

    public static function logoutUser()
    {
        setcookie('NDVR-RT', '', time() - 3600 * 24 * 30, "/", "", false, true);
        return ["success"=> true, "message"=> 'Successful account logout'];
    }

    public static function authUser()
    {   
        $query = "SELECT * FROM user WHERE id = ?";
        $access_token = Token::getBearerToken();
        $is_access_valid = Token::isTokenValid($access_token);

        if (!isset($_COOKIE['NDVR-VT'])) {
            $bytes = random_bytes(3);
            $visitor_id = bin2hex($bytes);
            $VT = Token::generateJWToken($visitor_id, 'visitor');
            setcookie('NDVR-VT', $VT, time() + 3600 * 24 * 30, "/", "", false, true);
        }

        try {
            if ($is_access_valid) {
                $user_id = Token::getPayload($access_token)->user_id;
                $user_data = Database::selectOne($query, [$user_id]);
                if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];
                return ["success"=> true, "user" => $user_data, "token" => $access_token, "message"=> 'Successful authorization'];
            }
    
            if (!isset($_COOKIE['NDVR-RT'])) return ["success"=> false, "message"=> 'Refresh token has not been received'];

    
            $refresh_token = $_COOKIE['NDVR-RT'];
            $is_refresh_valid = Token::isTokenValid($refresh_token);
            if(!$is_refresh_valid) return ["success"=> false, "message"=> 'Invalid refresh token'];
            $user_id = Token::getPayload($refresh_token)->user_id;
            $user_data = Database::selectOne($query, [$user_id]);
            if (!$user_data) return ["user" => null, "success"=> false, "message"=> 'No such user'];
    
            $new_access_token = Token::generateJWToken($user_data->id);
            return ["success"=> true, "user" => $user_data, "token" => $new_access_token, "message"=> 'Successful authorization'];

        } catch (\Exception $e) {
            Logme::warning('Error user authorization', [
                'message' => $e->getMessage(),
                'token' => $access_token,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["success"=> false, "user" => null, "token" => null, "message"=> 'Error user authorization '];
        }
    }

    public static function registerUser($data)
    {
        $is_username_valid = Validator::username($data['username']);
        $is_email_valid = Validator::email($data['email']);
        $is_password_valid = isset($data['password']) ? Validator::password($data['password']) : (isset($data['discord_id']) ? true : false);
         
        if (!$is_username_valid || !$is_email_valid || !$is_password_valid) return ["success"=> false, "message"=> 'Invalid credentials'];

        $bytes = random_bytes(8);
        $salt = bin2hex($bytes);
        $password = isset($data['password']) ? hash('sha256', $salt.$data['password']) : null;
        $avatar = isset($data['avatar']) ? $data['avatar'] : $_ENV['NDVR_USER_AVA'];
        $status = isset($data['status']) ? $data['status'] : 0;
        $discord_id = isset($data['discord_id']) ? $data['discord_id'] : null;

        $query_exist = "SELECT * FROM user WHERE username = ? OR email = ?";

        try {

            $is_user_exist = Database::selectOne($query_exist, [$data['username'], $data['email']]);
            if ($is_user_exist) {
                return ["success"=> false, "message"=> 'Such a user already exist'];
            }
    
            $user_id = Database::insert('user', [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $password,
                'created_date' => date('Y-m-d H:i:s'),
                'status' => $status,
                'discord_id' => $discord_id,
                'avatar' => $avatar
            ]);
    
            if(!$user_id) {
                return ["success"=> false, "message"=> 'Failed registration'];
            }
    
            $tokens_id = Database::insert('tokens', [
                'user_id' => $user_id
            ]);
            Database::insert('user_roles', [
                'user_id' => $user_id
            ]);
    
            $code = rand(11111, 99999);
            $code_id = Database::insert('registerconfirm', [
                'user_id' => $user_id,
                'code' => $code,
                'salt' => $salt
            ]);
    
            if(!$code_id || !$tokens_id) {
                Database::delete('user', ['id' => $user_id], ['id' => $user_id]);
                return ["success"=> false, "message"=> 'The data in the database could not be updated'];
            }
    
            $config = [
                'user_name' => $data['username'],
                'user_email' => $data['email'],
                'type' => 'registration',
                'token' => Token::generateJWToken($user_id, 'register'),
                'code' => $code,
            ];
    
            // dont send an email if registration via discord
            if (isset($data['discord_id'])) {
                Logme::info( 'New registration via Discord', [
                    'username' => $data['username'],
                    'email'=> $data['email'],
                    'time' => date('Y-m-d H:i:s')
                ]);
                return ["success"=> true, "email" => $data['email'], "message"=> 'Waiting for confirmation via email'];
            } 
    
            if (!Email::send($config)) {
                Database::delete('user', ['id' => $user_id], ['id' => $user_id]);
                return ["success"=> false, "message"=> 'The email could not be sent', "email" => $data['email']];
            }
    
            Logme::info( 'New registration', [
                'username' => $data['username'],
                'email'=> $data['email'],
                'time' => date('Y-m-d H:i:s')
            ]);
    
            return ["success"=> true, "email" => $data['email'], "message"=> 'Waiting for confirmation via email'];

        } catch (\Exception $e) {
            Logme::warning('Error user registration', [
                'message' => $e->getMessage(),
                'username' => $data['username'],
                'email' => $data['email'],
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["success"=> false, "email" => null, "message"=> 'Error user registration'];
        }
    }

    public static function activateUser($token, $code)
    {
        if (!Token::isTokenValid($token)) return ["success"=> false, "message"=> 'Invalid token'];
        $user_id = Token::getPayload($token)->user_id;
        $token_type = Token::getPayload($token)->token_type;
        if (!$user_id || !$token_type === 'register') return ["success"=> false, "message"=> 'Invalid token'];
        $query_code = "SELECT * FROM registerconfirm WHERE user_id = ?";

        try {
            $result_code = Database::selectOne($query_code, [$user_id]);
            if (!$result_code) return ["success"=> false, "message"=> 'No such user'];
            if ($result_code->code != $code) return ["success"=> false, "message"=> 'Invalid code'];
    
            $res_user = Database::update('user', ['status' => 1], ['id' => $user_id], ['id' => $user_id]);
            $res_salt = Database::update('registerconfirm', ['code' => rand(11111, 99999)], ['user_id' => $user_id], ['user_id' => $user_id]);
            if (!$res_user || !$res_salt) return ["success"=> false, "message"=> 'The data in the database could not be updated'];
            return ["success"=> true, "message"=> 'The user has been successfully activated'];
        } catch (\Exception $e) {
            Logme::warning('Error user activation', [
                'message' => $e->getMessage(),
                'token' => $token,
                'code' => $code,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["success"=> false, "message"=> 'Error user activation'];
        }
    }

    public static function deleteUser($email)
    {
        try {
            $result = Database::delete('user', ['email' => $email, 'status' => 0], ['email' => $email, 'status' => 0]);
            return $result
                ? ["success"=> true, "message"=> 'The user has been successfully deleted', 'email' => $email]
                : ["success"=> false, "message"=> 'The user cannot be deleted', 'email' => $email];
        } catch (\Exception $e) {
            Logme::warning('Error deleting user', [
                'message' => $e->getMessage(),
                'email' => $email,
                'time' => date('Y-m-d H:i:s')
            ]);
            ["success"=> false, "message"=> 'Error deleting user', 'email' => $email];
        }
    }

    public static function resetPassword($username, $email)
    {
        $is_username_valid = Validator::username($username);
        $is_email_valid = Validator::email($email);

        if (!$is_username_valid || !$is_email_valid) return ["success"=> false, "message"=> 'Invalid credentials', 'email' => $email];

        try {
            $user = Database::selectOne("SELECT * FROM user WHERE username = ? AND email = ?", [$username, $email]);
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

        } catch (\Exception $e) {
            Logme::warning('Error password reseting', [
                'message' => $e->getMessage(),
                'email' => $email,
                'username' => $username,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["success"=> false, "message"=> 'Error password reseting', 'email' => $email];
        }
    }

    public static function changePassword($data)
    {
        if (!Token::isTokenValid($data['token'])) return ["success"=> false, "message"=> 'Invalid token'];
        
        $user_id = Token::getPayload($data['token'])->user_id;
        $token_type = Token::getPayload($data['token'])->token_type;
        if (!$user_id || !$token_type === 'reset') return ["success"=> false, "message"=> 'Invalid token'];

        $is_password_valid = $data['password'] === $data['confirmPassword'] && Validator::password($data['password']);;
        if (!$is_password_valid) return ["success"=> false, "message"=> 'Invalid credentials'];
        $query_code = "SELECT * FROM registerconfirm WHERE user_id = ?";

        try {
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
        } catch (\Exception $e) {
            Logme::warning('Error password changing', [
                'message' => $e->getMessage(),
                'token' => $data['token'],
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["success"=> false, "message"=> 'Error password changing'];
        }
    }

    public static function discordUser($code, $auth_id)
    {   
        try {
            $discord_user_data = Curl::getUserFromDiscord($code);
            if (!$discord_user_data) return ["success"=> false, "message"=> 'Invalid code'];
            Database::insert('auth_ids', [
                'auth_id' => $auth_id
            ]);
    
            $user = [
                'discord_id' => $discord_user_data->id,
                'username' => $discord_user_data->username,
                'avatar' => 'https://cdn.discordapp.com/avatars/'.$discord_user_data->id.'/'.$discord_user_data->avatar.'.png',
                'email' => $discord_user_data->email,
                'status' => 1,
                'created_date' => date('Y-m-d H:i:s'),
            ];
      
            $my_user = Database::selectOne("SELECT * FROM user WHERE discord_id = ? OR username = ? OR email = ?", [$user['discord_id'], $user['username'], $user['email']]);
            if (!$my_user) {
                $res_reg = self::registerUser($user);
                if (!$res_reg['success']) return ["success"=> false, "message"=> $res_reg['message']];
                $my_user = Database::selectOne("SELECT * FROM user WHERE discord_id = ?", [$user['discord_id']]);
            }
    
            $AT = Token::generateJWToken($my_user->id);
            $RT = Token::generateJWToken($my_user->id, 'refresh');
            $RT_exp = Token::getPayload($RT)->exp;
    
            Database::update(
                'tokens',
                ['token_value' => $RT, 'expires_at' => $RT_exp],
                ['user_id' => $my_user->id],
                ['user_id' => $my_user->id],
            );
    
            setcookie('NDVR-RT', $RT, time() + 3600 * 24 * 30, "/", "", false, true);
            return ["success"=> true, "user" => $my_user, "token" => $AT, "message"=> 'Successful account login'];

        } catch (\Exception $e) {
            Logme::warning('Error Discord logining', [
                'message' => $e->getMessage(),
                'code' => $code,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ["success"=> false, "user" => null, "token" => null, "message"=> 'Error Discord logining'];
        }
    }
}
