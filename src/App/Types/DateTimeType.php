<?php

namespace App\Types;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use GraphQL\Type\Definition\ObjectType;
use App\Types\TypesRegistry;


class DateTimeType extends ObjectType {
    public function __construct() {
        $config = [
            'description' => 'DateTime',
            'fields' => function() {
                return [
                    'time' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Time h:m'
                    ],
                    'date' => [
                        'type' => TypesRegistry::string(),
                        'description' => 'Date d m y'
                    ],
                ];
            }
        ];
        parent::__construct($config);
    }
}