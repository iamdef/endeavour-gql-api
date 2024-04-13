<?php

namespace App\Resolvers;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Vendor/autoload.php';
use Dotenv\Dotenv;
use App\DB\Database;
use App\utils\PostPreparator;
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
        $updateId = $data['updateId'];

        try {
            if ($updateId) {
                $upd_res = Database::update(
                    'posts',
                    ['author' => $author, 'theme' => $theme, 'content' => json_encode($data), 'status' => 'черновик'],
                    ['id' => (int)$updateId],
                    ['id' => (int)$updateId],
                );
                if (!$upd_res) return ['success' => false, 'message' => 'Error when saving the updated post', 'id' => (int)$updateId, 'post' => $data];
                $post_res = self::getPost((int)$updateId);
                if (!$post_res['success']) ['success' => false, 'message' => 'Error when getting the updated post', 'id' => (int)$updateId, 'post' => $data];
                return ['success' => true, 'message' => 'The post has been successfully updated', 'id' => (int)$updateId, 'post' => $post_res['post']];
            }
            $post_id = Database::insert('posts', [
                'author' => $author,
                'theme' => $theme,
                'status' => 'черновик',
                'content' => json_encode($data)
            ]);
            $post_res = self::getPost($post_id);
            return ['success' => true, 'message' => 'The post has been successfully saved', 'id' => $post_id, 'post' => $post_res['post']];
        } catch (\Exception $e) {
            Logme::warning('Error saving the post', [
                'message' => $e->getMessage(),
                'author' => $author,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error post saving. See logs for more details.'];
        }
    }

    public static function getAllPosts($cursor, $limit, $sortDirection, $theme, $status)
    {   
        try {

            $direction = $sortDirection === 'old' ? 'ORDER BY posts.id' : 'ORDER BY posts.id DESC';

            // Строим условие WHERE для запроса
            $whereClause = ' WHERE 1=1'; // Начальное условие
            $params = ['limit' => (int)$limit, 'offset' => (int)$cursor]; // Параметры для запроса
            $whereTotal = ' WHERE 1=1'; // Для подсчета общего количества записей
            $paramsTotal = []; // Параметры для подсчета общего количества записей

            // Добавляем условие по теме, если оно задано
            if ($theme !== 'Все') {
                $whereClause .= ' AND posts.theme = :theme';
                $params['theme'] = strtolower($theme);
                $whereTotal .= ' AND theme = :theme';
                $paramsTotal['theme'] = strtolower($theme);
            }

            // Добавляем условие по статусу, если он задан
            if ($status !== 'all') {
                $whereClause .= ' AND posts.status = :status';
                $params['status'] = $status;
                $whereTotal .= ' AND status = :status';
                $paramsTotal['status'] = $status;
            }

            // Получаем общее количество записей
            $totalCount = Database::selectOne('SELECT COUNT(*) as total FROM posts' . $whereTotal, $paramsTotal);

            // Строим запрос для получения данных
            $query = 'SELECT posts.id, posts.author, posts.theme, posts.content, posts.status, posts.views, user.avatar
                    FROM posts
                    LEFT JOIN user ON posts.author = user.username'
                    . $whereClause . ' ' . $direction . ' LIMIT :limit OFFSET :offset';

            // Получаем данные постов
            $postsData = Database::select($query, $params);

            // Маппим данные постов
            $posts = array_map(function($postData) {
                // Увеличиваем счетчик просмотров
                $inc = self::incPostView($postData->id);
                return [
                    'id' => $postData->id,
                    'author' => [
                        'username' => $postData->author,
                        'avatar' => $postData->avatar
                    ],
                    'theme' => $postData->theme,
                    'content' => json_decode($postData->content, true),
                    'status' => $postData->status,
                    'views' => $inc['success'] ? $inc['views'] : $postData->views
                ];
            }, $postsData);

            $preparedPosts = PostPreparator::prepare($posts);

            return ['success' => true, 'message' => 'Successfull fetched posts', 'posts' => $preparedPosts, 'total' => $totalCount->total];
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
            $query = 'SELECT posts.id, posts.author, posts.theme, posts.content, posts.views, posts.status, user.avatar
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
                'status' => $post_data->status
            ];
            $inc = self::incPostView($post['id']);
            $post['views'] = $inc['success'] ? $inc['views'] : $post_data->views;
            
            $preparedPost = PostPreparator::prepare($post, true);

            return ['success' => true, 'message' => 'Successfull fetched post', 'post' => $preparedPost];
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
