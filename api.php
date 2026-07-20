<?php
// Configurações de erro e CORS
ini_set('display_errors', 0);
error_reporting(0);

// Gerenciamento de login por sessões nativas do PHP (Descomente se for usar ativamente)
// session_start();

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

// Captura o método e os dados enviados
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Se os dados vierem via parâmetros GET comuns na URL
if (empty($data)) {
    $data = $_GET;
}

$action = isset($data['action']) ? $data['action'] : '';

// Captura o ID do usuário (Tenta pela Session, senão via JSON)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ((isset($data['user_id']) && !empty($data['user_id'])) ? $data['user_id'] : null);

// FUNÇÕES AUXILIARES PARA RETORNAR DADOS ATUALIZADOS
function returnFolders($conn, $user_id) {
    $query = "SELECT id, name, bio, coverUrl FROM folders WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($folders as &$folder) {
        $itemQuery = "SELECT item_id AS uId, image_url AS img FROM folder_items WHERE folder_id = :folder_id";
        $itemStmt = $conn->prepare($itemQuery);
        $itemStmt->bindParam(':folder_id', $folder['id']);
        $itemStmt->execute();
        $folder['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode($folders);
    exit;
}

function returnFavorites($conn, $user_id) {
    $query = "SELECT item_id AS uId, image_url AS img FROM favorites WHERE user_id = :user_id ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($favorites);
    exit;
}

try {
    // AÇÃO: BUSCAR PASTAS
    if ($action === 'get_folders') {
        if (!$user_id) { echo json_encode([]); exit; }
        returnFolders($conn, $user_id);
    }

    // AÇÃO: BUSCAR FAVORITOS
    if ($action === 'get_favorites') {
        if (!$user_id) { echo json_encode([]); exit; }
        returnFavorites($conn, $user_id);
    }

    // AÇÃO: CRIAR PASTA
    if ($action === 'create_folder') {
        if (!$user_id) { http_response_code(401); echo json_encode(["error" => "Login necessário."]); exit; }
        if (!isset($data['name']) || empty(trim($data['name']))) { http_response_code(400); echo json_encode(["error" => "Nome obrigatório."]); exit; }

        $name = trim($data['name']);
        $bio = isset($data['bio']) ? trim($data['bio']) : '';
        $coverUrl = isset($data['coverUrl']) && !empty($data['coverUrl']) ? trim($data['coverUrl']) : 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809';

        $query = "INSERT INTO folders (user_id, name, bio, coverUrl) VALUES (:user_id, :name, :bio, :coverUrl)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':coverUrl', $coverUrl);
        $stmt->execute();

        returnFolders($conn, $user_id);
    }

    // AÇÃO: ATUALIZAR PASTA
    if ($action === 'update_folder') {
        if (!$user_id || !isset($data['folder_id'])) { http_response_code(400); echo json_encode(["error" => "Dados insuficientes."]); exit; }
        
        $query = "UPDATE folders SET name = :name, bio = :bio, coverUrl = :coverUrl WHERE id = :folder_id AND user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':name', trim($data['name']));
        $stmt->bindValue(':bio', isset($data['bio']) ? trim($data['bio']) : '');
        $stmt->bindValue(':coverUrl', isset($data['coverUrl']) ? trim($data['coverUrl']) : '');
        $stmt->bindParam(':folder_id', $data['folder_id']);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        returnFolders($conn, $user_id);
    }

    // AÇÃO: EXCLUIR PASTA
    if ($action === 'delete_folder') {
        if (!$user_id || !isset($data['folder_id'])) { http_response_code(400); echo json_encode(["error" => "Dados insuficientes."]); exit; }

        $stmtItems = $conn->prepare("DELETE FROM folder_items WHERE folder_id = :folder_id");
        $stmtItems->bindParam(':folder_id', $data['folder_id']);
        $stmtItems->execute();

        $stmt = $conn->prepare("DELETE FROM folders WHERE id = :folder_id AND user_id = :user_id");
        $stmt->bindParam(':folder_id', $data['folder_id']);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        returnFolders($conn, $user_id);
    }

    // AÇÃO: TOGGLE ITEM NA PASTA
    if ($action === 'toggle_folder_item') {
        if (!$user_id || !isset($data['folder_id']) || !isset($data['item_id'])) { http_response_code(400); echo json_encode(["error" => "Dados incompletos."]); exit; }

        $cStmt = $conn->prepare("SELECT id FROM folder_items WHERE folder_id = :folder_id AND item_id = :item_id");
        $cStmt->execute([':folder_id' => $data['folder_id'], ':item_id' => $data['item_id']]);

        if ($cStmt->fetch()) {
            $d = $conn->prepare("DELETE FROM folder_items WHERE folder_id = :folder_id AND item_id = :item_id");
            $d->execute([':folder_id' => $data['folder_id'], ':item_id' => $data['item_id']]);
        } else {
            $i = $conn->prepare("INSERT INTO folder_items (folder_id, item_id, image_url) VALUES (:folder_id, :item_id, :image_url)");
            $i->execute([':folder_id' => $data['folder_id'], ':item_id' => $data['item_id'], ':image_url' => $data['image_url']]);
        }
        returnFolders($conn, $user_id);
    }

    // AÇÃO: FAVORITAR
    if ($action === 'toggle_favorite') {
        if (!$user_id) { http_response_code(401); echo json_encode(["error" => "Login necessário."]); exit; }
        if (!isset($data['item_id']) || !isset($data['image_url'])) { http_response_code(400); echo json_encode(["error" => "Dados ausentes."]); exit; }

        $c = $conn->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND item_id = :item_id");
        $c->execute([':user_id' => $user_id, ':item_id' => $data['item_id']]);

        if ($c->fetch()) {
            $d = $conn->prepare("DELETE FROM favorites WHERE user_id = :user_id AND item_id = :item_id");
            $d->execute([':user_id' => $user_id, ':item_id' => $data['item_id']]);
        } else {
            $i = $conn->prepare("INSERT INTO favorites (user_id, item_id, image_url) VALUES (:user_id, :item_id, :image_url)");
            $i->execute([':user_id' => $user_id, ':item_id' => $data['item_id'], ':image_url' => $data['image_url']]);
        }
        returnFavorites($conn, $user_id);
    }

    http_response_code(400);
    echo json_encode(["error" => "Ação inválida."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro interno: " . $e->getMessage()]);
}
?>