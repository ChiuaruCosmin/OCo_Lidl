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
$id_obiect = $_POST['id_obiect'] ?? null;
if (!$id_obiect) {
    echo json_encode(['success' => false, 'message' => 'ID obiect lipsă']);
    exit;
}
try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT o.id FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE o.id = :id AND c.user = :user AND o.de_vanzare = 1");
    $stmt->execute([':id' => $id_obiect, ':user' => $username]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Obiectul nu există sau nu îți aparține.']);
        exit;
    }
    $stmt = $db->prepare("UPDATE obiecte SET de_vanzare = 0 WHERE id = :id");
    $stmt->execute([':id' => $id_obiect]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare la scoatere: ' . $e->getMessage()]);
} 