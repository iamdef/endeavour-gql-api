<?php
namespace App;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

use App\Types\TypesRegistry;
use App\DB\Database;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $config = [
        'host' => $_ENV['DB_SERVERNAME'],
        'database' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD']
    ];
    
    Database::init($config);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $query = $input['query'];

    $schema = new Schema([
        'query' => TypesRegistry::query()
    ]);

    $result = GraphQL::executeQuery($schema, $query);
    $output = $result->toArray();

} catch(\Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage()
            ]
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($output, JSON_THROW_ON_ERROR);
