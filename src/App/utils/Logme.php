<?php

namespace App\utils;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// require_once 'Vendor/autoload.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Formatter\LineFormatter;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('Vendor/'. '../');
$dotenv->load();

class Logme {
    private static $loggers = [];

    private static function getLogger($functionName, $level, $TG = FALSE) {
        if (!isset(self::$loggers[$functionName])) {
            $logger = new Logger($functionName);
            
            $streamHandler = new StreamHandler('Logs/log.log', $level);
            $lineFormatter = new LineFormatter("[%datetime%] %channel%[%level_name%]: %message% %context%\n", 'Y-m-d H:i:s', false, true);
            $streamHandler->setFormatter($lineFormatter);

            if ($TG) {
                $telegramHandler = new TelegramBotHandler($_ENV['TELEGRAM_BOT_TOKEN'], $_ENV['TELEGRAM_CHANNEL_ID']);
                $telegramHandler->setFormatter($lineFormatter);
                $logger->pushHandler($telegramHandler);
            }
            $logger->pushHandler($streamHandler);

            self::$loggers[$functionName] = $logger;
        }

        return self::$loggers[$functionName];
    }

    public static function info($message, $context = array()) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $functionName = $trace[1]['function'];
        self::getLogger($functionName, Logger::INFO)->info($message, $context);
    }

    public static function notice($message, $context = array()) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $functionName = $trace[1]['function'];
        self::getLogger($functionName, Logger::NOTICE, true)->notice($message, $context);
    }

    public static function warning($message, $context = array()) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $functionName = $trace[1]['function'];
        self::getLogger($functionName, Logger::WARNING)->warning($message, $context);
    }

    public static function error($message, $context = array()) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $functionName = $trace[1]['function'];
        self::getLogger($functionName, Logger::ERROR, true)->error($message, $context);
    }

    public static function critical($message, $context = array()) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $functionName = $trace[1]['function'];
        self::getLogger($functionName, Logger::CRITICAL, true)->critical($message, $context);
    }

    public static function alert($message, $context = array()) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $functionName = $trace[1]['function'];
        self::getLogger($functionName, Logger::ALERT, true)->alert($message, $context);
    }

    public static function emergency($message, $context = array()) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $functionName = $trace[1]['function'];
        self::getLogger($functionName, Logger::EMERGENCY, true)->emergency($message, $context);
    }
}
