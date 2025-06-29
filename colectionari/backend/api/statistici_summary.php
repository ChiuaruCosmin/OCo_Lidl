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

    $stmt = $db->prepare("SELECT COUNT(*) FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE c.user = :username AND o.de_vanzare = 0");
    $stmt->execute([':username' => $username]);
    $total_obiecte = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM colectii WHERE user = :username");
    $stmt->execute([':username' => $username]);
    $total_colectii = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT SUM(o.valoare) FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE c.user = :username AND o.de_vanzare = 0");
    $stmt->execute([':username' => $username]);
    $valoare_totala = (float)($stmt->fetchColumn() ?: 0);

    $stmt = $db->prepare("SELECT o.categorie, COUNT(*) as total FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE c.user = :username AND o.de_vanzare = 0 GROUP BY o.categorie ORDER BY total DESC LIMIT 3");
    $stmt->execute([':username' => $username]);
    $top_categorii_personale = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $top_categorii = $db->query("SELECT o.categorie, COUNT(*) as total FROM obiecte o JOIN colectii c ON o.colectie_id = c.id WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL) GROUP BY o.categorie ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $top_colectie = $db->query("SELECT c.titlu, COUNT(o.id) as nr_obiecte FROM colectii c JOIN obiecte o ON c.id = o.colectie_id WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL) GROUP BY c.id ORDER BY nr_obiecte DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $top_user = $db->query("SELECT c.user, COUNT(o.id) as total FROM colectii c JOIN obiecte o ON c.id = o.colectie_id WHERE o.de_vanzare = 0 AND (c.tip = 0 OR c.tip IS NULL) GROUP BY c.user ORDER BY total DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'personale' => [
            'total_obiecte' => $total_obiecte,
            'total_colectii' => $total_colectii,
            'valoare_totala' => $valoare_totala,
            'top_categorii_personale' => $top_categorii_personale
        ],
        'globale' => [
            'top_categorii' => $top_categorii,
            'top_colectie' => $top_colectie,
            'top_user' => $top_user
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare DB: ' . $e->getMessage()]);
} 