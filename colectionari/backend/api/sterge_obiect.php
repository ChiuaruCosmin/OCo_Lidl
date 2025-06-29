<?php
require_once __DIR__ . '/../lib/jwt.php';

header('Content-Type: text/plain');

$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo "Token lipsă";
    exit;
}

$token = $matches[1];

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    http_response_code(401);
    echo "Token invalid";
    exit;
}

$obiect_id = $_POST['id'] ?? '';

if (empty($obiect_id)) {
    http_response_code(400);
    echo "ID obiect lipsă";
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT o.id FROM obiecte o 
                         INNER JOIN colectii c ON o.colectie_id = c.id 
                         WHERE o.id = :obiect_id AND c.user = :username");
    $stmt->execute([':obiect_id' => $obiect_id, ':username' => $username]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo "Acces interzis";
        exit;
    }

    $stmt = $db->prepare("DELETE FROM obiecte WHERE id = :obiect_id");
    $stmt->execute([':obiect_id' => $obiect_id]);

    echo "Succes";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Eroare server: " . $e->getMessage();
}
?> 