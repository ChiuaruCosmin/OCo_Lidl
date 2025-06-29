<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../lib/jwt.php';

$public = isset($_GET['public']) && $_GET['public'] == '1';

if (!$public) {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token lipsă']);
        exit;
    }
    $token = $matches[1];
    try {
        $payload = verifyJWT($token);
        $username = $payload['username'];
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token invalid']);
        exit;
    }
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $populare = $db->query("
        SELECT c.id, c.titlu, c.imagine, c.user,
               (SELECT COUNT(*) FROM obiecte o WHERE o.colectie_id = c.id AND o.de_vanzare = 0) AS nr_obiecte
        FROM colectii c
        WHERE (c.tip = 0 OR c.tip IS NULL)
        ORDER BY nr_obiecte DESC
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($populare);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
}
