<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

class Validator {

    public static function username($username) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $username) && preg_match('/^.{3,}$/', $username);
    }

    public static function email($email) {
        return preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email);
    }

    public static function password($password) {
        return preg_match('/^.{6,}$/', $password);
    }

}