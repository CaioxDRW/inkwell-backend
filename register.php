<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Todos os campos (nome, e-mail e senha) são obrigatórios."]);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

try {
    // 1. Verifica se o e-mail já existe no banco
    $checkQuery = "SELECT id FROM usuarios WHERE email = :email LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(["error" => "Este e-mail já está cadastrado."]);
        exit;
    }

    // 2. Criptografa a senha usando a função nativa segura do PHP
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // 3. Insere os campos existentes na tabela usuarios
    $insertQuery = "INSERT INTO usuarios (name, email, password) VALUES (:name, :email, :password)";
    $insertStmt = $conn->prepare($insertQuery);
    
    $insertStmt->bindParam(':name', $name);
    $insertStmt->bindParam(':email', $email);
    $insertStmt->bindParam(':password', $password_hash);

    if ($insertStmt->execute()) {
        $newId = $conn->lastInsertId();
        http_response_code(201);
        echo json_encode([
            "message" => "Usuário registrado com sucesso.",
            "user" => [
                "id" => (int)$newId,
                "name" => $name,
                "email" => $email
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Erro ao registrar o usuário no banco de dados."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro interno no servidor de cadastro."]);
}
?>