<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('Vendor/' . '../');
$dotenv->load();

class PostPreparator {

    public static function getDate($timestamp, $numberFormat) {
        $timestamp = $timestamp / 1000;
        $time = new \DateTime("@$timestamp");
        $time->setTimezone(new \DateTimeZone('Europe/Moscow'));
        $day = $time->format('d');
        $monthsRU = array(
            'Jan' => 'Янв',
            'Feb' => 'Фев',
            'Mar' => 'Март',
            'Apr' => 'Aпр',
            'May' => 'Май',
            'Jun' => 'Июнь',
            'Jul' => 'Июль',
            'Aug' => 'Авг',
            'Sep' => 'Сент',
            'Oct' => 'Окт',
            'Nov' => 'Нояб',
            'Dec' => 'Дек',
        );
        $month = $monthsRU[$time->format('M')];
        $year = $time->format('Y');
        $timeFormat = $time->format('H:i'); // Формат времени в "час:минута"
    
        if ($numberFormat) {
            return $timeFormat;
        } else {
            return "{$day} {$month} {$year}";
        }
    }

    public static function prepare($raw, $single = false) {
        if ($single) {
            $content = $raw['content'];
            $post = array(
                'id' => $raw['id'] ?? $content['id'],
                'author' => $raw['author'] ?? $content['author'],
                'content' => $content,
                'theme' => $raw['theme'] ?? $content['theme'],
                'status' => $raw['status'] ?? $content['status'],
                'views' => $raw['views'] ?? $content['views']
            );
            return self::getPost($post);
        } else {
            $contents = array_map(function($post) {
                return $post['content'];
            }, $raw);
    
            $posts = array_map(function($post, $i) use ($contents) {
                return array(
                    'id' => $post['id'],
                    'author' => $post['author'],
                    'content' => $contents[$i],
                    'theme' => $post['theme'],
                    'status' => $post['status'],
                    'views' => $post['views']
                );
            }, $raw, array_keys($raw));

            return array_map(function($post) {
                return self::getPost($post);
            }, $posts);
    
        }
    }

    public static function prepareImage($postData) {
        $imagesArr = array_filter($postData['blocks'], function($block) {
            return $block['type'] === 'image';
        });
    
        $images = array_map(function($image) {
            return array(
                'src' => $image['data']['url'],
                'caption' => $image['data']['caption']
            );
        }, $imagesArr);
    
        return array_values($images);;
    }

    

    public static function getPost($post) {
        $id = $post['id'];
        $author = $post['author'];
        $timestamp = $post['content']['time'];
        $time = self::getDate($timestamp, true);
        $date = self::getDate($timestamp, false);
        $blocks = $post['content']['blocks'];
        $images = self::prepareImage($post['content']);
        $theme = $post['theme'];
        $views = $post['views'];
        $status = $post['status'];
        $content = $post['content'];

        $media_im = null;
        $caption = null;
        $media_em = null;
        $title = null;
        $paragraph = null;

        foreach ($blocks as $block) {
            if ($block['type'] === 'header' && !$title) {
                $title = $block['data']['text'];
            } elseif ($block['type'] === 'paragraph' && !$paragraph) {
                $paragraph = $block['data']['text'];
            } elseif ($block['type'] === 'image' && !$media_im) {
                $media_im = $block['data']['url'];
                $caption = $block['data']['caption'];
            } elseif ($block['type'] === 'embed' && !$media_em) {
                $media_em = $block['data']['embed'];
            }
        };

        $ready = array(
            'id' => $id,
            'author' => $author,
            'date' => $date,
            'time' => $time,
            'title' => $title,
            'theme' => $theme,
            'paragraph' => $paragraph,
            'images' => $images,
            'blocks' => $blocks,
            'content' => $content,
            'media_im' => $media_im,
            'caption' => $caption,
            'media_em' => $media_em,
            'status' => $status,
            'views' => $views,
        );

        return $ready;
    }

}