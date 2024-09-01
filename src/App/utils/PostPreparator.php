<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Vendor/autoload.php';

use DateTime;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('Vendor/' . '../');
$dotenv->load();

class PostPreparator {

    public static function getDate($timestamp, $numberFormat) {
        $timestamp = $timestamp / 1000;
        $time = new \DateTime("@$timestamp");
        $time->setTimezone(new \DateTimeZone('Europe/Moscow'));
        $day = ltrim($time->format('d'), '0');
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
                'views' => $raw['views'] ?? $content['views'],
                'mentioned' => $raw['mentioned'] ?? null,
                'created_at' => $raw['created_at'],
                'updated_at' => $raw['updated_at']
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
                    'views' => $post['views'],
                    'mentioned' => $post['mentioned'] ?? null,
                    'created_at' => $post['created_at'],
                    'updated_at' => $post['updated_at']
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

        // work with date
        $created_at_str = $post['created_at'];
        $updated_at_str = $post['updated_at'];
        // setting timezone
        $timezone = new \DateTimeZone('Europe/Moscow');
        $dateTimeCreated = DateTime::createFromFormat('Y-m-d H:i:s', $created_at_str, $timezone);
        $dateTimeUpdated = DateTime::createFromFormat('Y-m-d H:i:s', $updated_at_str, $timezone);
        $dateTimeCreated->setTimezone(new \DateTimeZone('UTC'));
        $dateTimeUpdated->setTimezone(new \DateTimeZone('UTC'));

        $timestamp = $dateTimeCreated->getTimestamp() * 1000; 
        $timestamp_upd = $dateTimeUpdated->getTimestamp() * 1000;

        $created_at_time = self::getDate((int) strtotime($created_at_str) * 1000, true);
        $created_at_date = self::getDate((int) strtotime($created_at_str) * 1000, false);
        
        $blocks = $post['content']['blocks'];
        $images = self::prepareImage($post['content']);
        $theme = $post['theme'];
        $views = $post['views'];
        $status = $post['status'];
        $content = $post['content'];
        $mentioned = $post['mentioned'];

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
            'timestamp' => $timestamp,
            'timestamp_upd' => $timestamp_upd,
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
            'mentioned' => $mentioned,
            'created_at_time' => $created_at_time,
            'created_at_date' => $created_at_date,
        );

        return $ready;
    }

}