<?php

namespace App\utils;

class Token {
    public static function base64url_encode($data) { // функция кодировки
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}