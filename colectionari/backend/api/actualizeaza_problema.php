<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Neautentificat']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Format token invalid']);
    exit;
}
$token = substr($authHeader, 7);
try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token invalid']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date lipsă']);
    exit;
}
$id = (int)$data['id'];
$status = $data['status'];
if (!in_array($status, ['rezolvat', 'ignorat'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Status invalid']);
    exit;
}
try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT admin FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $isAdmin = $stmt->fetchColumn();
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Doar adminii pot accesa.']);
        exit;
    }
    $stmt = $db->prepare("UPDATE probleme SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare la actualizare: ' . $e->getMessage()]);
} 