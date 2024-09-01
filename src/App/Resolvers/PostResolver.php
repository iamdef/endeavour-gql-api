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
        $mentioned = isset($data['mentioned']) ? $data['mentioned'] : [];

        try {
            // saving or updating post
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
                $post_id = (int)$updateId;
            } else {
                $post_id = Database::insert('posts', [
                    'author' => $author,
                    'theme' => $theme,
                    'status' => 'черновик',
                    'content' => json_encode($data)
                ]);
                if (!$post_id) return ['success' => false, 'message' => 'Error when saving the new post', 'post' => $data];
            }

            // saving or updating user mentions
            if ($mentioned) self::saveMentions($mentioned, $post_id);
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

    public static function saveMentions($mentioned, $post_id)
    {
        try {
            // getting the saved mentioned users for this post
            $currentMentions = Database::select(
                'SELECT user.username FROM post_mentions
                LEFT JOIN user ON post_mentions.user_id = user.id
                WHERE post_mentions.post_id = :post_id',
                ['post_id' => $post_id]
            );

            $currentMentions = array_map(function($mention) {
                return $mention->username;
            }, $currentMentions);

            // deleting and adding new mentions
            $mentionsToDelete = array_diff($currentMentions, $mentioned);
            $mentionsToAdd = array_diff($mentioned, $currentMentions);

            // deleting
            foreach ($mentionsToDelete as $username) {
                $user = Database::selectOne(
                    'SELECT id FROM user WHERE username = :username',
                    ['username' => $username]
                );
    
                if ($user) {
                    $user_id = $user->id;
                    $del_mention_res = Database::delete('post_mentions', [
                        'post_id' => $post_id,
                        'user_id' => $user_id
                    ], [
                        'post_id' => $post_id,
                        'user_id' => $user_id
                    ]);
    
                    if (!$del_mention_res) {
                        return ['success' => false, 'message' => "Error when deleting user mention for $username", 'id' => $post_id];
                    }
                }
            }

            // adding
            foreach ($mentionsToAdd as $username) {
                $user = Database::selectOne(
                    'SELECT id FROM user WHERE username = :username',
                    ['username' => $username]
                );
    
                if (!$user) {
                    return ['success' => false, 'message' => "User with username $username not found"];
                }
                $user_id = $user->id;

                $exists = Database::selectOne(
                    'SELECT * FROM post_mentions WHERE post_id = :post_id AND user_id = :user_id',
                    ['post_id' => $post_id, 'user_id' => $user_id]
                );
    
                if (!$exists) {
                    $ins_mention_res = Database::insert('post_mentions', [
                        'post_id' => $post_id,
                        'user_id' => $user_id
                    ]);
                    if (!$ins_mention_res) return ['success' => false, 'message' => 'Error when inserting user mentions', 'id' => $post_id];
                } 
            }

            return ['success' => true, 'message' => 'The mentioned users have been successfully saved', 'id' => $post_id];
        } catch (\Exception $e) {
            Logme::warning('Error saving the mentioned users', [
                'message' => $e->getMessage(),
                'post_id' => $post_id,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error post saving. See logs for more details.'];
        }

    }

    public static function getAllPosts($cursor, $limit, $sortDirection, $theme, $status, $person)
    {   
        try {

            $direction = $sortDirection === 'old' ? 'ORDER BY posts.id' : 'ORDER BY posts.id DESC';

            // create WHERE query
            $whereClause = ' WHERE 1=1'; // initial WHERE clause
            $params = ['limit' => (int)$limit, 'offset' => (int)$cursor]; // query params
            $whereTotal = ' WHERE 1=1'; // calculate total
            $paramsTotal = []; // params for calculate total

            // adding theme filter
            if ($theme !== 'Все') {
                $whereClause .= ' AND posts.theme = :theme';
                $params['theme'] = strtolower($theme);
                $whereTotal .= ' AND theme = :theme';
                $paramsTotal['theme'] = strtolower($theme);
            }

            // adding status filter
            if ($status !== 'all') {
                $whereClause .= ' AND posts.status = :status';
                $params['status'] = $status;
                $whereTotal .= ' AND status = :status';
                $paramsTotal['status'] = $status;
            }

            // adding person filter
            if ($person) {
                // Join the post_mentions table to filter by mentioned user
                $whereClause .= ' AND EXISTS (SELECT 1 FROM post_mentions 
                                                LEFT JOIN user ON post_mentions.user_id = user.id
                                                WHERE post_mentions.post_id = posts.id
                                                AND user.username = :person)';
                $params['person'] = $person;
            }

            // getting total

            if ($person) {

            $totalCount = Database::selectOne('
                SELECT COUNT(DISTINCT posts.id) as total
                FROM posts
                INNER JOIN post_mentions ON posts.id = post_mentions.post_id
                INNER JOIN user ON post_mentions.user_id = user.id
                WHERE user.username = :person
                AND (:theme = "Все" OR posts.theme = :theme)
                AND (:status = "all" OR posts.status = :status)
            ', [
                'person' => $person,
                'theme' => $theme,
                'status' => $status
            ]);

            } else {
                $totalCount = Database::selectOne('SELECT COUNT(*) as total FROM posts' . $whereTotal, $paramsTotal);
            }

            // create query
            $query = 'SELECT posts.id, posts.author, posts.theme,
                            posts.content, posts.status, posts.views,
                            posts.created_at, posts.updated_at, user.avatar
                    FROM posts
                    LEFT JOIN user ON posts.author = user.username'
                    . $whereClause . ' ' . $direction . ' LIMIT :limit OFFSET :offset';

            $postsData = Database::select($query, $params);

            // mapping posts
            $posts = array_map(function($postData) {
                // incrementing views
                $inc = self::incPostView($postData->id);
                $post = [
                    'id' => $postData->id,
                    'author' => [
                        'username' => $postData->author,
                        'avatar' => $postData->avatar
                    ],
                    'theme' => $postData->theme,
                    'content' => json_decode($postData->content, true),
                    'status' => $postData->status,
                    'views' => $inc['success'] ? $inc['views'] : $postData->views,
                    'created_at' => $postData->created_at,
                    'updated_at' => $postData->updated_at
                ];

                // getting mentioned users
                $mentioned_query = 'SELECT user.username, user.avatar FROM post_mentions
                LEFT JOIN user ON post_mentions.user_id = user.id
                WHERE post_mentions.post_id = :post_id';
                $mentioned_users = Database::select($mentioned_query, ['post_id' => $postData->id]);

                if ($mentioned_users) {
                    $mentioned = array_map(function ($user) {
                        return [
                            'username' => $user->username,
                            'avatar' => $user->avatar
                        ];
                    }, $mentioned_users);
                    $post['mentioned'] = $mentioned;
                } else {
                    $post['mentioned'] = null;
                }
    
                return $post;

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
            $query = 'SELECT posts.id, posts.author, posts.theme, posts.content,
                posts.created_at, posts.updated_at, posts.views, posts.status, user.avatar
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
                'status' => $post_data->status,
                'created_at' => $post_data->created_at,
                'updated_at' => $post_data->updated_at
            ];
            $inc = self::incPostView($post['id']);
            $post['views'] = $inc['success'] ? $inc['views'] : $post_data->views;

            // getting mentioned users
            $mentioned_query = 'SELECT user.username, user.avatar FROM post_mentions
                LEFT JOIN user ON post_mentions.user_id = user.id
                WHERE post_mentions.post_id = :post_id';

            $mentioned_users = Database::select($mentioned_query, ['post_id' => $id]);

            if ($mentioned_users) {
                $mentioned = array_map(function ($user) {
                    return [
                    'username' => $user->username,
                    'avatar' => $user->avatar
                    ];
                    }, $mentioned_users);
        
                // Adding mentioned users to the post
                $post['mentioned'] = $mentioned;
            } else {
                $post['mentioned'] = null;
            }

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

    public static function changePostStatus($ids, $status)
    {
        $sanitizedIds = array_map('intval', $ids);
        $upd_results = [];
        $upd_posts = [];
        try {
            foreach ($sanitizedIds as $id) {
                $upd_res = Database::update(
                    'posts',
                    ['status' => $status],
                    ['id' => $id],
                    ['id' => $id],
                ); 
                $upd_results[$id] = $upd_res;

                $get_upd_res = self::getPost($id);
                $get_upd_res['success'] && array_push($upd_posts, $get_upd_res['post']);
            }

            // if (in_array(false, $upd_results, true)) {
            //     $errorIds = array_keys($upd_results, false, true);
            //     return ['success' => false, 'message' => 'Error when changing status for posts with IDs: ' . implode(', ', $errorIds), 'ids' => $sanitizedIds, 'status' => $status];
            // }
            
            return ['success' => true, 'message' => 'The post status has been successfully updated', 'ids' => $sanitizedIds, 'status' => $status, 'posts' => $upd_posts];
        } catch (\Exception $e) {
            Logme::warning('Error changing post status', [
                'message' => $e->getMessage(),
                'id' => $sanitizedIds,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error changing post status. See logs for more details.'];
        }
    }

    public static function deletePost($ids)
    {
        $sanitizedIds = array_map('intval', $ids);
        $upd_results = [];
        try {
            foreach ($sanitizedIds as $id) {
                $upd_res = Database::delete('posts', ['id' => $id], ['id' => $id]);
                $upd_results[$id] = $upd_res;
            }

            if (in_array(false, $upd_results, true)) {
                $errorIds = array_keys($upd_results, false, true);
                return ['success' => false, 'message' => 'Error when deleting posts with IDs: ' . implode(', ', $errorIds), 'ids' => $sanitizedIds];
            }

            return ['success' => true, 'message' => 'The post has been successfully deleted', 'ids' => $sanitizedIds];
        } catch (\Exception $e) {
            Logme::warning('Error deleting posts', [
                'message' => $e->getMessage(),
                'id' => $sanitizedIds,
                'time' => date('Y-m-d H:i:s')
            ]);
            return ['success' => false, 'message' => 'Error deleting posts. See logs for more details.'];
        }
    }

}
