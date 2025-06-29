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
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalid']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("SELECT o.categorie, COUNT(*) as nr_obiecte FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL) GROUP BY o.categorie ORDER BY nr_obiecte DESC LIMIT 5");
    $categorii = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categorii);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare DB: ' . $e->getMessage()]);
} 