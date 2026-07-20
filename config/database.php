<?php
$host = 'mysql.railway.internal'; 
$db   = 'railway';
$user = 'root';
$pass = getenv('MYSQLPASSWORD');
$port = '3306';                  

try {
    $conn = new PDO("mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db . ";charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(["error" => "Falha na conexão com o banco de dados: " . $exception->getMessage()]);
    exit();
}
?>