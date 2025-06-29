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

    $stmt = $db->prepare("SELECT strftime('%Y-%m', o.data_adaugare) AS luna, COUNT(*) AS nr_obiecte FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE c.user = :username AND o.de_vanzare = 0 GROUP BY luna ORDER BY luna ASC");
    $stmt->execute([':username' => $username]);
    $evolutie = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($evolutie);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare DB: ' . $e->getMessage()]);
} 