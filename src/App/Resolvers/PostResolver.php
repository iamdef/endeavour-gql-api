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

class PostResolver
{
    public static function savePost($data)
    {           
        $author = $data['author'];
        $theme = $data['theme'];

        try {
            $post_id = Database::insert('posts', [
                'author' => $author,
                'theme' => $theme,
                'content' => json_encode($data)
            ]);
            return ['success' => true, 'message' => 'The post has been successfully saved ', 'id' => $post_id, 'post' => $data];
        } catch (\Exception $e) {
            Logme::warning('Error saving post', [
                'message' => $e->getMessage(),
                'author' => $author,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error post saving'];
        }
    }

    public static function getAllPosts($initial, $offset)
    {   
        try {
            $posts_data = Database::select('SELECT * FROM posts WHERE id >= ? AND id <= ?', [$initial, $offset]);
            $posts = array_map(function($posts_data) {
                return json_decode($posts_data->content, true);
            }, $posts_data);
            $ids = array_map(function($posts_data) {
                return $posts_data->id;
            }, $posts_data);

            return ['success' => true, 'message' => 'The post has been successfully saved', 'posts' => $posts, 'ids' => $ids];
        } catch (\Exception $e) {
            Logme::warning('Error saving post', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error fetching the posts'];
        }
    }

}
