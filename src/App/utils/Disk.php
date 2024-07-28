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

    public static function getQueryURL(string $filePath, string $dirPath = '', string $queryType = '')
    {
        /* getting URL for upload */
        $arrParams = [
            'path' => $dirPath . basename($filePath),
            'overwrite' => 'true',
        ];
        $directories = [
            'upload' => 'resources/upload',
            'download' => 'resources/download'
        ];

        $urlQuery = $_ENV['YNDX_DISK_API_URL'] . $directories[$queryType];
        $resultQuery = self::sendQuery($urlQuery, $arrParams);
        return $resultQuery;
    }

    /**
     * Uploads a file to the specified directory.
     *
     * @param string $filePath The path to the file to upload.
     * @param string $dirPath The directory path to upload the file to. Default is an empty string.
     * @return string
     */
    public static function upload(string $filePath, string $dirPath = '')
    {
        $resultQuery = self::getQueryURL($filePath, $dirPath, 'upload');

        if (empty($resultQuery['error'])) {
            /* if there are no errors, then sending the file */
            $fp = fopen($filePath, 'r');
        
            $ch = curl_init($resultQuery['href']);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        
            return $http_code;
        } else {
            return $resultQuery['message'];
        }
    }

    public static function delete(array $arrParams)
    {
        // params example
        // $arrParams = [
        //     'path' => '/file.png',
        //     'permanently' => 'false', // move to the trash or permanently delete
        //     'fields' => 'name,_embedded.items.path' // fields to return
        // ];

        $urlQuery = $_ENV['YNDX_DISK_API_URL'] . 'resources/';
        return self::sendQuery($urlQuery, $arrParams, 'DELETE');
    }

    public static function publish(array $arrParams, $statusPublic = true) {
        $urlQuery = $_ENV['YNDX_DISK_API_URL'] . 'resources/' . (($statusPublic) ? 'publish' : 'unpublish');
        return self::sendQuery($urlQuery, $arrParams, 'PUT');
    }

    public static function getPublicURL(array $arrParams)
    {
        $urlQuery = $_ENV['YNDX_DISK_API_URL'] . 'resources/';
        return self::sendQuery($urlQuery, $arrParams);
    }

    public static function download(string $filePath, string $dirPath = '')
    {
        $resultQuery = self::getQueryURL($filePath, $dirPath, 'download');

        if(empty($resultQuery['error'])) {
            $file_name = $dirPath . basename($filePath);
            $file = @fopen($file_name, 'w');
        
            $ch = curl_init($resultQuery['href']);
            curl_setopt($ch, CURLOPT_FILE, $file);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $_ENV['YNDX_DISK_ACCESS_TOKEN']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $resultQuery = curl_exec($ch);
            curl_close($ch);
    
            fclose($file);
    
            return [
                'message' => 'Файл успешно загружен',
                'path' => $file_name,
            ];
        } else {
            return $resultQuery;
        }
    }
}