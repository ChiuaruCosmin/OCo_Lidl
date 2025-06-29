<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? '';
if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token lipsă']);
    exit;
}
$token = $matches[1];
try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token invalid']);
    exit;
}
$id = $_POST['id'] ?? '';
$titlu = trim($_POST['titlu'] ?? '');
if ($id === '' || $titlu === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID sau titlu lipsă']);
    exit;
}
try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT imagine FROM colectii WHERE id = :id AND user = :user");
    $stmt->execute([':id' => $id, ':user' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acces interzis']);
        exit;
    }
    $imgPath = $row['imagine'];
    if (isset($_FILES['imagine']) && $_FILES['imagine']['tmp_name']) {
        $targetDir = '../../frontend/uploads/avatars/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['imagine']['name'], PATHINFO_EXTENSION));
        $imgName = uniqid('col_', true) . '.' . $ext;
        $targetFile = $targetDir . $imgName;
        if (!move_uploaded_file($_FILES['imagine']['tmp_name'], $targetFile)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Eroare la upload imagine']);
            exit;
        }
        $imgPath = 'uploads/avatars/' . $imgName;
    }
    $stmt = $db->prepare("UPDATE colectii SET titlu = :titlu, imagine = :imagine WHERE id = :id AND user = :user");
    $stmt->execute([
        ':titlu' => $titlu,
        ':imagine' => $imgPath,
        ':id' => $id,
        ':user' => $username
    ]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Eroare DB: ' . $e->getMessage()]);
} 