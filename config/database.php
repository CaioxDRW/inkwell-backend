<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

$host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: 'mysql.railway.internal';
$db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '3306';

try {
    $conn = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    $response = ['error' => 'Falha na conexão com o banco de dados.'];
    if (getenv('APP_ENV') === 'development') {
        $response['details'] = $exception->getMessage();
    }
    echo json_encode($response);
    exit();
}
?>
