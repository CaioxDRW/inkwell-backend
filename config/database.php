<?php
$host = "crossover.proxy.rlwy.net";
$port = "19792";
$db_name = "railway";
$username = "root";
$password = "gNfYNCuyTCZdGnWnUDTHUOXAVwTTquiO";

try {
    $conn = new PDO("mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db_name . ";charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(["error" => "Falha na conexão com o banco de dados: " . $exception->getMessage()]);
    exit();
}
?>