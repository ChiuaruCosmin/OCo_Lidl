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

$titlu = trim($_POST['titlu'] ?? '');
if ($titlu === '' || !isset($_FILES['imagine'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Titlu sau imagine lipsă']);
    exit;
}

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

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("INSERT INTO colectii (user, titlu, imagine) VALUES (:user, :titlu, :imagine)");
    $stmt->execute([
        ':user' => $username,
        ':titlu' => $titlu,
        ':imagine' => $imgPath
    ]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Eroare DB: ' . $e->getMessage()]);
} 