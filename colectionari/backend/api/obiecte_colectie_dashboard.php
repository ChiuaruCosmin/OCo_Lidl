<?php
require_once __DIR__ . '/../lib/jwt.php';
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautentificat']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Format token invalid']);
    exit;
}
$token = substr($authHeader, 7);
try {
    $payload = verifyJWT($token);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalid']);
    exit;
}
try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $id = $_GET['id'] ?? null;
    $titlu = $_GET['titlu'] ?? null;
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM obiecte WHERE colectie_id = :id AND de_vanzare = 0");
        $stmt->execute([':id' => $id]);
    } elseif ($titlu) {
        $stmt = $db->prepare("SELECT o.* FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE c.titlu = :titlu AND o.de_vanzare = 0");
        $stmt->execute([':titlu' => $titlu]);
    } else {
        echo json_encode([]);
        exit;
    }
    $obiecte = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($obiecte);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la obiecte: ' . $e->getMessage()]);
} 