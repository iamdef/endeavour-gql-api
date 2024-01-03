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

$email = $_GET['email'];

Database::init($config);

$query = "SELECT user.*
FROM user
WHERE user.email = ? AND user.status = ?";
$result = Database::selectOne($query, [$email, 1]);

if ($result) {
    echo "data: " . json_encode(['confirm_status' => 'success']) . "\n\n";
    ob_flush();
    flush();
    sleep(10);
} 

?>


