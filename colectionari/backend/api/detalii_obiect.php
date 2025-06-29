<?php
require_once __DIR__ . '/../lib/jwt.php';

header('Content-Type: application/json');

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

$obiect_id = $_GET['id'] ?? null;

if (!$obiect_id) {
    echo json_encode([]);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM obiecte WHERE id = :id AND de_vanzare = 0");
    $stmt->execute([':id' => $obiect_id]);
    
    $obiect = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$obiect) {
        http_response_code(403);
        echo json_encode(['error' => 'Acces interzis']);
        exit;
    }

    $obiect['categorie'] = $obiect['categorie'] ?? 'Necunoscută';

    echo json_encode($obiect);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la detalii obiect: ' . $e->getMessage()]);
}
?> 