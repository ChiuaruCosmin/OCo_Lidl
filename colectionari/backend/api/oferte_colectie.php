<?php
require_once __DIR__ . '/../lib/jwt.php';
header('Content-Type: application/json');

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
    echo json_encode(['success' => false, 'message' => 'Token lipsă.']);
    exit;
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'];
if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Format token invalid.']);
    exit;
}
$token = substr($authHeader, 7);

try {
    $payload = verifyJWT($token);
    $username = $payload['username'];
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token invalid: ' . $e->getMessage()]);
    exit;
}

$id_colectie = isset($_GET['id_colectie']) ? intval($_GET['id_colectie']) : 0;
if (!$id_colectie) {
    echo json_encode(['success' => false, 'message' => 'ID colecție lipsă.']);
    exit;
}

try {
    $db = new PDO("sqlite:" . __DIR__ . '/../db/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT user FROM colectii WHERE id = :id");
    $stmt->execute([':id' => $id_colectie]);
    $proprietar = $stmt->fetchColumn();
    if ($proprietar !== $username) {
        echo json_encode(['success' => false, 'message' => 'Nu ai dreptul să vezi aceste oferte.']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM oferte_colectii WHERE id_colectie = :id ORDER BY data DESC");
    $stmt->execute([':id' => $id_colectie]);
    $oferte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'oferte' => $oferte]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Eroare DB: ' . $e->getMessage()]);
} 