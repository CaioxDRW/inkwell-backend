<?php
// Configurações de erro e CORS
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';

// Captura o corpo da requisição JSON vinda do React
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "E-mail e senha são obrigatórios."]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

try {
    // Seleciona os campos da tabela usuarios
    $query = "SELECT id, name, email, password FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se o usuário existe e se a senha bate com o hash criptografado
    if ($user && password_verify($password, $user['password'])) {
        // Se você gerenciar login por sessões nativas ativamente, pode descomentar:
        // session_start();
        // $_SESSION['user_id'] = $user['id'];

        unset($user['password']);
        
        http_response_code(200);
        echo json_encode([
            "message" => "Login realizado com sucesso.",
            "user" => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "E-mail ou senha incorretos."]);
    }

} catch (PDOException $e) {
    // PROTEÇÃO: Mesmo que dê erro no banco de dados, não mostra o SQL pro usuário
    http_response_code(401); 
    echo json_encode(["error" => "E-mail ou senha incorretos."]);
}
?>