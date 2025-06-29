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
    $titlu = $_GET['titlu'] ?? '';
    if ($titlu !== '') {
        $stmt = $db->prepare("SELECT id, user, titlu, imagine FROM colectii WHERE LOWER(titlu) LIKE :titlu ORDER BY titlu ASC");
        $stmt->execute([':titlu' => '%' . strtolower($titlu) . '%']);
        $colectii = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->query("SELECT id, user, titlu, imagine FROM colectii ORDER BY titlu ASC");
        $colectii = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode(['success' => true, 'colectii' => $colectii]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare la listare: ' . $e->getMessage()]);
} 