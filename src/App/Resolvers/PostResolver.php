<?php

namespace App\Resolvers;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Vendor/autoload.php';
use Dotenv\Dotenv;
use App\DB\Database;
use App\utils\Access;
use App\utils\Logme;
use App\utils\Token;


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
            Logme::warning('Error saving the post', [
                'message' => $e->getMessage(),
                'author' => $author,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error post saving'];
        }
    }

    public static function getAllPosts($cursor, $limit, $sortDirection)
    {   
        try {

            $direction = $sortDirection === 'old' ? 'ORDER BY posts.id' : 'ORDER BY posts.id DESC';
            $totalCount = Database::selectOne('SELECT COUNT(*) as total FROM posts');

            $query = 'SELECT posts.id, posts.author, posts.theme, posts.content, posts.views, user.avatar
                    FROM posts
                    LEFT JOIN user ON posts.author = user.username '.$direction.' LIMIT :limit OFFSET :offset';
            $posts_data = Database::select($query, ['limit' => (int)$limit, 'offset' => (int)$cursor]);


            $posts = array_map(function($posts_data) {
                return [
                    'id' => $posts_data->id,
                    'author' => [
                        'username' => $posts_data->author,
                        'avatar' => $posts_data->avatar
                    ],
                    'theme' => $posts_data->theme,
                    'content' => json_decode($posts_data->content, true),
                    'views' => $posts_data->views
                ];   
            }, $posts_data);

            return ['success' => true, 'message' => 'Successfull fetched posts', 'posts' => $posts, 'total' => $totalCount->total];
        } catch (\Exception $e) {
            Logme::warning('Error fetching posts', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error fetching posts'];
        }
    }

    public static function getPost($id)
    {   
        try {
            $query = 'SELECT posts.id, posts.author, posts.theme, posts.content, posts.views, user.avatar
                FROM posts
                LEFT JOIN user ON posts.author = user.username WHERE posts.id = :id';
            $post_data = Database::selectOne($query, ['id' => $id]);
            if (!$post_data) return ["post" => null, "success"=> false, "message"=> 'No such post'];

            $post = [
                'id' => $post_data->id,
                'author' => [
                    'username' => $post_data->author,
                    'avatar' => $post_data->avatar
                ],
                'theme' => $post_data->theme,
                'content' => json_decode($post_data->content, true),
                'views' => $post_data->views
            ];
            return ['success' => true, 'message' => 'Successfull fetched post', 'post' => $post];
        } catch (\Exception $e) {
            Logme::warning('Error fetching post', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error fetching post'];
        }
    }

    public static function incPostView($post_id)
    {
        try {
            if (!isset($_COOKIE['NDVR-VT'])) return ['success' => false, 'message' => 'Visitor token has not been received'];
            $visitor_token = $_COOKIE['NDVR-VT'];
            $is_visitor_valid = Token::isTokenValid($visitor_token);
            if (!$is_visitor_valid) return ['success' => false, 'message' => 'Invalid visitor token'];
            $visitor_id = Token::getPayload($visitor_token)->user_id;
            
            $post = Database::selectOne('SELECT * FROM posts WHERE id = :post_id', ['post_id' => $post_id]);
            $has_visitor_seen = Database::selectOne('SELECT * FROM post_views WHERE post_id = :post_id AND visitor_id = :visitor_id', ['post_id' => $post_id, 'visitor_id' => $visitor_id]);
            if ($has_visitor_seen) return ['success' => false, 'message' => 'The visitor has already seen', 'id' => $post_id, 'views' => $post->views];
    
            $increment = Database::update('posts', ['views' => $post->views + 1], ['id' => $post_id], ['id' => $post_id]);
            if(!$increment) return ['success' => false, 'message' => 'Failed to increase the number of views', 'id' => $post_id];

            $register_view = Database::insert('post_views', ['post_id' => $post_id, 'visitor_id' => $visitor_id]);
            if(!$register_view) return ['success' => false, 'message' => 'Failed to register the view', 'id' => $post_id];

            return ['success' => true, 'message' => 'Successfully increased the number of views', 'id' => $post_id, 'views' => $post->views + 1];
        } catch (\Exception $e) {
            Logme::warning('Error increasing post views', [
                'message' => $e->getMessage(),
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error increasing post views'];
        }

    }

}
