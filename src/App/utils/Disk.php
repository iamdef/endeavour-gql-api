<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('Vendor/' . '../');
$dotenv->load();

class Disk
{
    public static function sendQuery(string $urlQuery, array $arrQuery = [], string $methodQuery = 'GET')
    {
        if($methodQuery == 'POST') {
            $fullUrlQuery = $urlQuery;
        } else {
            $fullUrlQuery = $urlQuery . '?' . http_build_query($arrQuery);
        }

        $ch = curl_init($fullUrlQuery);
        switch ($methodQuery) {
            case 'PUT':
                curl_setopt($ch, CURLOPT_PUT, true);
                break;
    
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arrQuery));
                break;
    
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $_ENV['YNDX_DISK_ACCESS_TOKEN']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $resultQuery = curl_exec($ch);
        curl_close($ch);

        return (!empty($resultQuery)) ? json_decode($resultQuery, true) : [];
    }

    public static function getInfo()
    {
        $urlQuery= $_ENV['YNDX_DISK_API_URL'];

        return self::sendQuery($urlQuery);
    }
}