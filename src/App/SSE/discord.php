<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../../vendor/autoload.php';
use Dotenv\Dotenv;
use App\DB\Database;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

$config = [
    'host' => $_ENV['DB_SERVERNAME'],
    'database' => $_ENV['DB_NAME'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD']
];

$auth_id = $_GET['auth'];

Database::init($config);

$query = "SELECT *
FROM auth_ids
WHERE auth_id = ?";
$result = Database::selectOne($query, [$auth_id]);

if ($result->id) {
    echo "data: " . json_encode(['auth_status' => 'success']) . "\n\n";
    Database::delete('auth_ids', ['auth_id' => $auth_id], ['auth_id' => $auth_id]);
    ob_flush();
    flush();
    sleep(3);
} 

?>


