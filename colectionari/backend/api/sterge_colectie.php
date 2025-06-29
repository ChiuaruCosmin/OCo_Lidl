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

$colectie_id = $_POST['id'] ?? '';

if (empty($colectie_id)) {
    http_response_code(400);
    echo "ID colecție lipsă";
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id FROM colectii WHERE id = :id AND user = :username");
    $stmt->execute([':id' => $colectie_id, ':username' => $username]);
    
    if (!$stmt->fetch()) {
        $db->rollBack();
        http_response_code(403);
        echo "Acces interzis";
        exit;
    }

    $stmt = $db->prepare("DELETE FROM obiecte WHERE colectie_id = :colectie_id");
    $stmt->execute([':colectie_id' => $colectie_id]);

    $stmt = $db->prepare("DELETE FROM colectii WHERE id = :id AND user = :username");
    $stmt->execute([':id' => $colectie_id, ':username' => $username]);

    $db->commit();
    echo "Succes";

} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo "Eroare server: " . $e->getMessage();
}
?> 