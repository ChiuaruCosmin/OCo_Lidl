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
$pret = $_POST['pret'] ?? '';

if (empty($obiect_id) || empty($pret)) {
    http_response_code(400);
    echo "Parametri lipsă";
    exit;
}

if (!is_numeric($pret) || $pret <= 0) {
    http_response_code(400);
    echo "Preț invalid";
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT o.id, o.titlu, o.valoare FROM obiecte o 
                         INNER JOIN colectii c ON o.colectie_id = c.id 
                         WHERE o.id = :obiect_id AND c.user = :username AND o.de_vanzare = 0");
    $stmt->execute([':obiect_id' => $obiect_id, ':username' => $username]);
    
    $obiect = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$obiect) {
        $db->rollBack();
        http_response_code(403);
        echo "Obiect indisponibil sau acces interzis";
        exit;
    }

    $stmt = $db->prepare("UPDATE obiecte SET de_vanzare = 1, pret = :pret WHERE id = :obiect_id");
    $stmt->execute([':obiect_id' => $obiect_id, ':pret' => $pret]);

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