<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido no corpo da requisição.']);
    exit();
}

if (!is_array($data) || empty($data['email']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'E-mail e senha são obrigatórios.']);
    exit();
}

$email = trim($data['email']);
$password = $data['password'];

try {
    $query = 'SELECT id, name, email, password FROM usuarios WHERE email = :email LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        http_response_code(200);
        echo json_encode([
            'message' => 'Login realizado com sucesso.',
            'user' => $user,
        ]);
        exit();
    }

    http_response_code(401);
    echo json_encode(['error' => 'E-mail ou senha incorretos.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno no servidor.']);
}
?>
