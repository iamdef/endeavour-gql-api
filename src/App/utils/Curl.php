<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// require_once 'Vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('Vendor/' . '../');
$dotenv->load();

class Curl {

    public static function apiRequest($url, $post = array(), $headers = array()) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL turn off in dev
    
    
        if (count($post) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
    
        
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $response = curl_exec($ch);
    
        if ($response === FALSE) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
    
        curl_close($ch);
    
        return json_decode($response);
    }

    public static function getUserFromDiscord($authCode) {
        $ch = curl_init($_ENV['DISCORD_API_URL']);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
        $clientId = $_ENV['DISCORD_CLIENT_ID'] ?? null;
        $clientSecret = $_ENV['DISCORD_CLIENT_SECRET'] ?? null;
        $redirectUri = $_ENV['DISCORD_REDIRECT_URI'] ?? null;
    
        if (!$clientId || !$clientSecret || !$redirectUri) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
    
        $token = self::apiRequest($_ENV['DISCORD_TOKEN_URL'], array(
            "grant_type" => "authorization_code",
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $authCode
        ));
    
        if (!property_exists($token, 'access_token')) {
            return false;
        }
        
        $headers[] = 'Authorization: Bearer ' . $token->access_token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $response = curl_exec($ch);
    
        if ($response === FALSE) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
    
        curl_close($ch);
    
        return json_decode($response);
    }

}